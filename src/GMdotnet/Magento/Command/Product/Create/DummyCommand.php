<?php

namespace GMdotnet\Magento\Command\Product\Create;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class DummyCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    const DEFAULT_PRODUCT_ATTRIBUTE_SET_ID = 4;
    const DEFAULT_PRODUCT_SHORT_DESCRIPTION = "Lorem ipsum short";
    const DEFAULT_PRODUCT_DESCRIPTION = "Lorem ipsum";
    const DEFAULT_PRODUCT_VISIBILITY = \Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
    const DEFAULT_PRODUCT_STATUS = \Mage_Catalog_Model_Product_Status::STATUS_ENABLED;

    protected function configure()
    {
        $this
            ->setName('product:create:dummy')
            //->addArgument('type', InputArgument::OPTIONAL, 'Product Type [simple]')
            ->addArgument('product-type', InputArgument::OPTIONAL, 'Product Type [simple, configurable, grouped]')
            ->addArgument('product-number', InputArgument::OPTIONAL, 'Number of products to create')
            ->addArgument('sku-prefix', InputArgument::OPTIONAL, 'Prefix for product\'s sku')
            ->setDescription('(Experimental) Create a dummy product [gmdotnet]')
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

        $output->writeln("<warning>This is experimental and it only create sample product.</warning>\r\n");

        /**
         * ARGUMENTS
         */
        

        $helper = $this->getHelper('question');
        // PRODUCT TYPE
        if(is_null($input->getArgument('product-type'))) {
            $question = new ChoiceQuestion(
                'Please select Magento Product type (default: simple)',
                array(\Mage_Catalog_Model_Product_Type::TYPE_SIMPLE),
                // TODO: create other type
                //array('simple', 'configurable', 'grouped'),
                0
            );
            $question->setErrorMessage('Magento Product Type "%s" is invalid.');
            $input->setArgument('product-type', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>Product Type selected: '.$input->getArgument('product-type')."</info>\r\n");

        // NUMBER OF PRODUCTS
        if(is_null($input->getArgument('product-number'))) {
            $question = new Question("Please enter the number of product to create (default 1): \r\n", 1);
            $question->setValidator(function ($answer) {
                $answer = (int)($answer);
                if (!is_int($answer) || $answer <= 0) {
                    throw new \RuntimeException(
                        'Please enter an integer value or > 0'
                    );
                }
                return $answer;
            });
            $input->setArgument('product-number', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>Number of products to create: ' . $input->getArgument('product-number')."</info>\r\n");

        // SKU PREFIX
        if(is_null($input->getArgument('sku-prefix'))) {
            $question = new Question("Please enter the product's sku prefix (default MAGPROD-): \r\n", "MAGPROD-");
            $input->setArgument('sku-prefix', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>SKU PREFIX: ' . $input->getArgument('sku-prefix')."</info>\r\n");


        /**
         * LOOP to create products
         */ 
        for($i = 0; $i <= $input->getArgument('product-number'); $i++)
        {
            switch($input->getArgument('product-type'))
            {
                /**
                 * SIMPLE PRODUCT
                 */
                case \Mage_Catalog_Model_Product_Type::TYPE_SIMPLE:
                {
                    $sku = $input->getArgument('sku-prefix').$i;
                    
                    // Check if product exists
                    $collection = \Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('sku')
                        ->addAttributeToFilter('sku', array('eq' => $sku))
                    ;
                    $_size = $collection->getSize();
                    if($_size > 0)
                    {
                        $output->writeln("<comment>".$i.") PRODUCT: WITH SKU: '".$sku."' EXISTS! Skip</comment>\n");
                        break;
                    }
                    
                    // Creating product
                    try {
                        $product = \Mage::getModel('catalog/product');
                        $product->setTypeId(\Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
                        $product->setName($sku." ".$i);
                        $product->setShortDescription(self::DEFAULT_PRODUCT_SHORT_DESCRIPTION);
                        $product->setDescription(self::DEFAULT_PRODUCT_DESCRIPTION);
                        $product->setSku($sku);
                        $product->setAttributeSetId(self::DEFAULT_PRODUCT_ATTRIBUTE_SET_ID);
                        $product->setStatus(self::DEFAULT_PRODUCT_STATUS);
                        $product->setVisibility(self::DEFAULT_PRODUCT_VISIBILITY);

                        $product->save();
                    }
                    catch(\Exception $e)
                    {
                        $output->writeln("<error>".$e->getMessage()."</error>");
                        die;
                    }

                    // Check if product was created correctly
                    $collection = \Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('sku')
                        ->addAttributeToFilter('sku', array('eq' => $product->getSku()))
                    ;
                    $_size = $collection->getSize();
                    if($_size > 0)
                    {
                        $output->writeln("<comment>".$i.") PRODUCT: '" . $product->getName()."' WITH SKU: '".$product->getSku()."' CREATED!</comment>\r\n");
                    }
                    else {
                        $output->writeln("<error>".$i.") ERROR WITH PRODUCT: '" . $product->getName()."' SKU: '".$product->getSku()."' </error>\r\n");
                    }
                    unset($_size);
                    unset($product);
                }
            }
        }
    }
}