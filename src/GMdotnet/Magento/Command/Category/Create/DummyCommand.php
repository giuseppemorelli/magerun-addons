<?php

namespace GMdotnet\Magento\Command\Category\Create;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class DummyCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    const DEFAULT_CATEGORY_NAME = "My Awesome Category";
    const DEFAULT_CATEGORY_STATUS = 1; // enabled
    const DEFAULT_CATEGORY_ANCHOR = 1; // enabled
    const DEFAULT_CATEGORY_ROOT = 2; // Default Category
    const DEFAULT_STORE_ID = 1;
    

    protected function configure()
    {
        $this
            ->setName('category:create:dummy')
            ->addArgument('children-categories-number', InputArgument::OPTIONAL, "Number of children for each category created (default: 0 - use '-1' for random from 0 to 5)")
            ->addArgument('category-name-prefix', InputArgument::OPTIONAL, "Category Name Prefix (default: 'My Awesome Category')")
            ->addArgument('category-number', InputArgument::OPTIONAL, 'Number of categories to create (default: 1)')
            ->setDescription('(Experimental) Create a dummy category [gmdotnet]')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        $this->initMagento();

        $output->writeln("<warning>This is experimental and it only create sample categories.</warning>\r\n");

        /**
         * ARGUMENTS
         */
        $helper = $this->getHelper('question');
        $_argument = array();

        // NUMBER OF CHILDREN CATEGORIES
        if(is_null($input->getArgument('children-categories-number'))) {
            $question = new Question("Number of children for each category created (default: 0 - use '-1' for random from 0 to 5): ", 0);
            $question->setValidator(function ($answer) {
                $answer = (int)($answer);
                if (!is_int($answer) || $answer < -1) {
                    throw new \RuntimeException(
                        "Please enter an integer value or >= -1"
                    );
                }
                return $answer;
            });
            $input->setArgument('children-categories-number', $helper->ask($input, $output, $question));
        }
        if($input->getArgument('children-categories-number') == -1)
            $input->setArgument('children-categories-number', rand(0, 5));
        
        $output->writeln('<info>Number of categories children to create: ' . $input->getArgument('children-categories-number')."</info>\r\n");
        $_argument['children-categories-number'] = $input->getArgument('children-categories-number');

        // CATEGORY NAME PREFIX
        if(is_null($input->getArgument('category-name-prefix'))) {
            $question = new Question("Please enter the category name prefix (default '".self::DEFAULT_CATEGORY_NAME."'): ", self::DEFAULT_CATEGORY_NAME);
            $input->setArgument('category-name-prefix', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>CATEGORY NAME PREFIX: ' . $input->getArgument('category-name-prefix')."</info>\r\n");
        $_argument['category-name-prefix'] = $input->getArgument('category-name-prefix');

        // NUMBER OF CATEGORIES
        if(is_null($input->getArgument('category-number'))) {
            $question = new Question("Please enter the number of categories to create (default 1): ", 1);
            $question->setValidator(function ($answer) {
                $answer = (int)($answer);
                if (!is_int($answer) || $answer <= 0) {
                    throw new \RuntimeException(
                        'Please enter an integer value or > 0'
                    );
                }
                return $answer;
            });
            $input->setArgument('category-number', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>Number of categories to create: ' . $input->getArgument('category-number')."</info>\r\n");
        $_argument['category-number'] = $input->getArgument('category-number');
                
        /**
         * LOOP to create categories
         */ 
        for($i = 0; $i < $_argument['category-number']; $i++)
        {
            if(!is_null($_argument['category-name-prefix']))
            {
                $name = $_argument['category-name-prefix']." ".$i;
            }
            else {
                $name = self::DEFAULT_CATEGORY_NAME." ".$i;
            }
            
            // Check if product exists
            $collection = \Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('name', array('eq' => $name))
            ;
            $_size = $collection->getSize();
            if($_size > 0)
            {
                $output->writeln("<comment>".$i.") CATEGORY: WITH NAME: '".$name."' EXISTS! Skip</comment>\r");
                $_argument['category-number']++;
                continue;
            }
            unset($collection);

            $category = \Mage::getModel('catalog/category');
            $category->setName($name);
            $category->setIsActive(self::DEFAULT_CATEGORY_STATUS);
            $category->setDisplayMode('PRODUCTS');
            $category->setIsAnchor(self::DEFAULT_CATEGORY_ANCHOR);
            $category->setStoreId(self::DEFAULT_STORE_ID);
            $parentCategory = \Mage::getModel('catalog/category')->load(self::DEFAULT_CATEGORY_ROOT);
            $category->setPath($parentCategory->getPath());

            $category->save();
            $_parent_id = $category->getId();
            $output->writeln("<comment>".$i.") CATEGORY: '" . $category->getName()."' WITH ID: '".$category->getId()."' CREATED!</comment>\r");
            unset($category);

            // CREATE CHILDREN CATEGORIES
            for($j = 0; $j < $_argument['children-categories-number']; $j++)
            {
                $name_child = $name." child ".$j;

                $category = \Mage::getModel('catalog/category');
                $category->setName($name_child);
                $category->setIsActive(self::DEFAULT_CATEGORY_STATUS);
                $category->setDisplayMode('PRODUCTS');
                $category->setIsAnchor(self::DEFAULT_CATEGORY_ANCHOR);
                $category->setStoreId(self::DEFAULT_STORE_ID);
                $parentCategory = \Mage::getModel('catalog/category')->load($_parent_id);
                $category->setPath($parentCategory->getPath());

                $category->save();
                $output->writeln("<comment>".$i.") CATEGORY CHILD: '" . $category->getName()."' WITH ID: '".$category->getId()."' CREATED!</comment>\r");
                unset($category);
            }
        }
    }
}