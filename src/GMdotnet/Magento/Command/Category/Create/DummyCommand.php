<?php

namespace GMdotnet\Magento\Command\Category\Create;

use Mage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class DummyCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    const DEFAULT_CATEGORY_NAME   = "My Awesome Category";
    const DEFAULT_CATEGORY_STATUS = 1; // enabled
    const DEFAULT_CATEGORY_ANCHOR = 1; // enabled
    const DEFAULT_STORE_ID        = 1; // Default Store ID

    protected function configure()
    {
        $this->setName('category:create:dummy')
             ->addArgument('store-id', InputArgument::OPTIONAL, 'Id of Store to create categories (default: 1)')
             ->addArgument('category-number', InputArgument::OPTIONAL, 'Number of categories to create (default: 1)')
             ->addArgument('children-categories-number', InputArgument::OPTIONAL, "Number of children for each category created (default: 0 - use '-1' for random from 0 to 5)")
             ->addArgument('category-name-prefix', InputArgument::OPTIONAL, "Category Name Prefix (default: 'My Awesome Category')")
             ->setDescription('Create a dummy category [gmdotnet]');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        $this->initMagento();

        $output->writeln("<warning>This only create sample categories, do not use on production environment</warning>");

        // Ask for Arguments
        $_argument = $this->askForArguments($input, $output);

        /**
         * Loop to create categories
         */
        for($i = 0; $i < $_argument['category-number']; $i++) {
            if(!is_null($_argument['category-name-prefix'])) {
                $name = $_argument['category-name-prefix']." ".$i;
            }
            else {
                $name = self::DEFAULT_CATEGORY_NAME." ".$i;
            }

            // Check if product exists
            $collection = Mage::getModel('catalog/category')->getCollection()
                                ->addAttributeToSelect('name')
                                ->addAttributeToFilter('name', array('eq' => $name));
            $_size = $collection->getSize();
            if($_size > 0) {
                $output->writeln("<comment>CATEGORY: WITH NAME: '".$name."' EXISTS! Skip</comment>");
                $_argument['category-number']++;
                continue;
            }
            unset($collection);

            $_category_root_id = Mage::app()->getStore($_argument['store-id'])->getRootCategoryId();

            $category = Mage::getModel('catalog/category');
            $category->setName($name);
            $category->setIsActive(self::DEFAULT_CATEGORY_STATUS);
            $category->setDisplayMode('PRODUCTS');
            $category->setIsAnchor(self::DEFAULT_CATEGORY_ANCHOR);

            if(Mage::getVersion() === "1.5.1.0") {
                $category->setStoreId(array(0, $_argument['store-id']));
            }
            else {
                $category->setStoreId($_argument['store-id']);
            }
            $parentCategory = Mage::getModel('catalog/category')->load($_category_root_id);
            $category->setPath($parentCategory->getPath());

            $category->save();
            $_parent_id = $category->getId();
            $output->writeln("<comment>CATEGORY: '".$category->getName()."' WITH ID: '".$category->getId()."' CREATED!</comment>");
            unset($category);

            // Create children Categories
            for($j = 0; $j < $_argument['children-categories-number']; $j++) {
                $name_child = $name." child ".$j;

                $category = Mage::getModel('catalog/category');
                $category->setName($name_child);
                $category->setIsActive(self::DEFAULT_CATEGORY_STATUS);
                $category->setDisplayMode('PRODUCTS');
                $category->setIsAnchor(self::DEFAULT_CATEGORY_ANCHOR);

                if(Mage::getVersion() === "1.5.1.0") {
                    $category->setStoreId(array(0, $_argument['store-id']));
                }
                else {
                    $category->setStoreId($_argument['store-id']);
                }
                $parentCategory = Mage::getModel('catalog/category')->load($_parent_id);
                $category->setPath($parentCategory->getPath());

                $category->save();
                $output->writeln("<comment>CATEGORY CHILD: '".$category->getName()."' WITH ID: '".$category->getId()."' CREATED!</comment>");
                unset($category);
            }
        }
    }

    /**
     * Ask for command arguments
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array
     */
    private function askForArguments($input, $output)
    {
        $helper = $this->getHelper('question');
        $_argument = array();

        // Store ID
        if(is_null($input->getArgument('store-id'))) {
            $store_id = Mage::getModel('core/store')->getCollection()
                            ->addFieldToSelect('*')
                            ->addFieldToFilter('store_id', array('gt' => 0))
                            ->setOrder('store_id', 'ASC');
            $_store_ids = array();

            foreach($store_id as $item) {
                $_store_ids[$item['store_id']] = $item['store_id']."|".$item['code'];
            }

            $question = new ChoiceQuestion('Please select Store ID (default: 1)', $_store_ids, self::DEFAULT_STORE_ID);
            $question->setErrorMessage('Store ID "%s" is invalid.');
            $response = explode("|", $helper->ask($input, $output, $question));
            $input->setArgument('store-id', $response[0]);
        }
        $output->writeln('<info>Store ID selected: '.$input->getArgument('store-id')."</info>");
        $_argument['store-id'] = $input->getArgument('store-id');

        // Number of Categories
        if(is_null($input->getArgument('category-number'))) {
            $question = new Question("Please enter the number of categories to create (default 1): ", 1);
            $question->setValidator(function($answer) {
                $answer = (int)($answer);
                if(!is_int($answer) || $answer <= 0) {
                    throw new \RuntimeException('Please enter an integer value or > 0');
                }

                return $answer;
            });
            $input->setArgument('category-number', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>Number of categories to create: '.$input->getArgument('category-number')."</info>");
        $_argument['category-number'] = $input->getArgument('category-number');

        // Number of children categories
        if(is_null($input->getArgument('children-categories-number'))) {
            $question = new Question("Number of children for each category created (default: 0 - use '-1' for random from 0 to 5): ", 0);
            $question->setValidator(function($answer) {
                $answer = (int)($answer);
                if(!is_int($answer) || $answer < -1) {
                    throw new \RuntimeException("Please enter an integer value or >= -1");
                }

                return $answer;
            });
            $input->setArgument('children-categories-number', $helper->ask($input, $output, $question));
        }
        if($input->getArgument('children-categories-number') == -1)
            $input->setArgument('children-categories-number', rand(0, 5));

        $output->writeln('<info>Number of categories children to create: '.$input->getArgument('children-categories-number')."</info>");
        $_argument['children-categories-number'] = $input->getArgument('children-categories-number');

        // Category name prefix
        if(is_null($input->getArgument('category-name-prefix'))) {
            $question = new Question("Please enter the category name prefix (default '".self::DEFAULT_CATEGORY_NAME."'): ", self::DEFAULT_CATEGORY_NAME);
            $input->setArgument('category-name-prefix', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>CATEGORY NAME PREFIX: '.$input->getArgument('category-name-prefix')."</info>");
        $_argument['category-name-prefix'] = $input->getArgument('category-name-prefix');

        return $_argument;
    }
}
