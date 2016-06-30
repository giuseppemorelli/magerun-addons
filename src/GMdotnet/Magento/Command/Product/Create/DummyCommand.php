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
    const DEFAULT_PRODUCT_ATTRIBUTE_SET_ID = 4; // Default
    const DEFAULT_PRODUCT_NAME = "Lorem ipsum dolor sit amet";
    const DEFAULT_PRODUCT_SHORT_DESCRIPTION = "Lorem ipsum dolor sit amet. SHORT";
    const DEFAULT_PRODUCT_DESCRIPTION = "Lorem ipsum dolor sit amet. LONG";
    const DEFAULT_PRODUCT_VISIBILITY = 4; // \Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH
    const DEFAULT_PRODUCT_STATUS = 1; // \Mage_Catalog_Model_Product_Status::STATUS_ENABLED
    const DEFAULT_WEBSITE_ID = 1; // \Mage_Core_Model_App::DISTRO_STORE_ID -> magento 1.9

    protected function configure()
    {
        $this
            ->setName('product:create:dummy')
            ->addArgument('attribute-set-id', InputArgument::OPTIONAL, 'Attribute Set Id (default: Default with ID 4)')
            ->addArgument('product-type', InputArgument::OPTIONAL, 'Product Type (default: simple)')
            ->addArgument('sku-prefix', InputArgument::OPTIONAL, 'Prefix for product\'s sku (default: MAGPROD-)')
            ->addArgument('category-ids', InputArgument::OPTIONAL, 'Categories for product association (comma separated - default null)')
            ->addArgument('product-status', InputArgument::OPTIONAL, 'Product Status (default: enabled)')
            ->addArgument('product-visibility', InputArgument::OPTIONAL, 'Product Visibility (default: visibile_both)')
            ->addArgument('product-number', InputArgument::OPTIONAL, 'Number of products to create')

            // TODO: other arguments
            //->addArgument('product-type', InputArgument::OPTIONAL, 'Product Type [simple, configurable, grouped]')
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
        $_argument = array();

        // ATTRIBUTE SET ID
        if(is_null($input->getArgument('attribute-set-id'))) {

            $attribute_set = \Mage::getModel('eav/entity_attribute_set')->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('entity_type_id', array('eq' => 4))
                ->setOrder('attribute_set_id', 'ASC');
            ;
            $_attribute_sets = array();
            
            foreach($attribute_set as $item)
            {
                $_attribute_sets[$item['attribute_set_id']] = $item['attribute_set_id']."|".$item['attribute_set_name'];
            }
            
            $question = new ChoiceQuestion(
                'Please select Attribute Set (default: Default)',
                $_attribute_sets,
                4
            );
            $question->setErrorMessage('Attribute Set "%s" is invalid.');
            $response = explode("|", $helper->ask($input, $output, $question));
            $input->setArgument('attribute-set-id', $response[0]);
        }
        $output->writeln('<info>Attribute Set selected: '.$input->getArgument('attribute-set-id')."</info>\r\n");
        $_argument['attribute-set-id'] = $input->getArgument('attribute-set-id');

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
        $_argument['product-type'] = $input->getArgument('product-type');

        // SKU PREFIX
        if(is_null($input->getArgument('sku-prefix'))) {
            $question = new Question("Please enter the product's sku prefix (default MAGPROD-): \r\n", "MAGPROD-");
            $input->setArgument('sku-prefix', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>SKU PREFIX: ' . $input->getArgument('sku-prefix')."</info>\r\n");
        $_argument['sku-prefix'] = $input->getArgument('sku-prefix');

        // CATEGORY IDS
        if(is_null($input->getArgument('category-ids'))) {
            $question = new Question("Please enter the category ids for product association (comma separated): \r\n", null);
            $input->setArgument('category-ids', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>Category Ids choosed: ' . $input->getArgument('category-ids')."</info>\r\n");

        // PRODUCT STATUS
        if(is_null($input->getArgument('product-status'))) {
            $_prod_status = array();
            $_prod_status[\Mage_Catalog_Model_Product_Status::STATUS_ENABLED] = \Mage_Catalog_Model_Product_Status::STATUS_ENABLED."|Enabled";
            $_prod_status[\Mage_Catalog_Model_Product_Status::STATUS_DISABLED] = \Mage_Catalog_Model_Product_Status::STATUS_DISABLED."|Disabled";
            
            $question = new ChoiceQuestion(
                'Please select Product Status (default: enabled)',
                $_prod_status,
                0
            );
            $question->setErrorMessage('Product Status "%s" is invalid.');
            $response = explode("|", $helper->ask($input, $output, $question));
            $input->setArgument('product-status', $response[0]);
        }
        $output->writeln('<info>Product Status selected: '.$input->getArgument('product-status')."</info>\r\n");
        $_argument['product-status'] = $input->getArgument('product-status');

        // PRODUCT VISIBILITY
        if(is_null($input->getArgument('product-visibility'))) {
            $_prod_visibility = array();
            $_prod_visibility[\Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE] = \Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE."|Not Visible";
            $_prod_visibility[\Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG] = \Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG."|Visible in Catalog";
            $_prod_visibility[\Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH] = \Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH."|Visibile in Search";
            $_prod_visibility[\Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH] = \Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH."|Visible Both";

            $question = new ChoiceQuestion(
                'Please select Product Visibility (default: visible both)',
                $_prod_visibility,
                3
            );
            $question->setErrorMessage('Product Visibility "%s" is invalid.');
            $response = explode("|", $helper->ask($input, $output, $question));
            $input->setArgument('product-visibility', $response[0]);
        }
        $output->writeln('<info>Product Visibility selected: '.$input->getArgument('product-visibility')."</info>\r\n");
        $_argument['product-visibility'] = $input->getArgument('product-visibility');

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
        $_argument['product-number'] = $input->getArgument('product-number');
                
        /**
         * LOOP to create products
         */ 
        for($i = 0; $i < $_argument['product-number']; $i++)
        {
            switch($_argument['product-type'])
            {
                /**
                 * SIMPLE PRODUCT
                 */
                case \Mage_Catalog_Model_Product_Type::TYPE_SIMPLE:
                {
                    $sku = $_argument['sku-prefix'].$i;

                    // Check if product exists
                    $collection = \Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('sku')
                        ->addAttributeToFilter('sku', array('eq' => $sku))
                    ;
                    $_size = $collection->getSize();
                    if($_size > 0)
                    {
                        $output->writeln("<comment>".$i.") PRODUCT: WITH SKU: '".$sku."' EXISTS! Skip</comment>\r");
                        continue;
                    }
                    unset($collection);
                    
                    // Creating product
                    try {
                        $product = \Mage::getModel('catalog/product');
                        $product->setTypeId(\Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
                        if(!is_null($_argument['attribute-set-id']))
                        {
                            $product->setAttributeSetId($_argument['attribute-set-id']);
                        }
                        else {
                            $product->setAttributeSetId(self::DEFAULT_PRODUCT_ATTRIBUTE_SET_ID);
                        }
                        $product->setWebsiteIds(array(self::DEFAULT_WEBSITE_ID));

                        $product->setName(self::DEFAULT_PRODUCT_NAME." ".$sku);
                        $product->setDescription(self::DEFAULT_PRODUCT_DESCRIPTION);
                        $product->setShortDescription(self::DEFAULT_PRODUCT_SHORT_DESCRIPTION);
                        $product->setSku($sku);
                        $product->setWeight(rand(1, 99));

                        if(!is_null($_argument['product-status']))
                        {
                            $product->setStatus($_argument['product-status']);
                        }
                        else {
                            $product->setStatus(self::DEFAULT_PRODUCT_STATUS);
                        }
                        if(!is_null($_argument['product-visibility']))
                        {
                            $product->setVisibility($_argument['product-visibility']);
                        }
                        else {
                            $product->setVisibility(self::DEFAULT_PRODUCT_VISIBILITY);
                        }
                        $product->setPrice(rand(10, 999));
                        $product->setTaxClassId(0);

                        // IMAGES
                        if (!file_exists(\Mage::getBaseDir('media').DS."import/")) {
                            mkdir(\Mage::getBaseDir('media').DS."import/", 0777, true);
                        }
                        $product->setMediaGallery (array('images' => array(), 'values'=>array()));
                        $_tmp_image = array();
                        $_file_image = array();
                        for($count = 0; $count < 3; $count++)
                        {
                            $_tmp_image[$count] = file_get_contents('http://lorempixel.com/400/400/');
                            $_file_image[$count] = \Mage::getBaseDir('media').DS."import/".$sku."-".sha1($sku.$count).".jpg";
                            file_put_contents($_file_image[$count], $_tmp_image[$count]);
                            $product->addImageToMediaGallery($_file_image[$count], array('image','thumbnail','small_image'), false, false);
                        }
                        
                        $product->setStockData(array(
                                'is_in_stock' => 1,
                                'qty' => rand(0, 999)
                            )
                        );

                        // CATEGORIES
                        if(!is_null($input->getArgument('category-ids')))
                        {
                            $_category_ids = explode(',', $input->getArgument('category-ids'));
                            $product->setCategoryIds($_category_ids);
                        }
                        
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
                        $output->writeln("<comment>".$i.") PRODUCT: '" . $product->getName()."' WITH SKU: '".$product->getSku()."' CREATED!</comment>\r");
                    }
                    else {
                        $output->writeln("<error>".$i.") ERROR WITH PRODUCT: '" . $product->getName()."' SKU: '".$product->getSku()."' </error>\r\n");
                    }
                    unset($_size);
                    unset($product);
                    break;
                }
                default:
                    $output->writeln("<error>INVALID PRODUCT TYPE</error>\r\n");
            }
        }
    }
}