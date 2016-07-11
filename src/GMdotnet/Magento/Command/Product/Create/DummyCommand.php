<?php

namespace GMdotnet\Magento\Command\Product\Create;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
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

    var $input;
    var $output;
    var $_configurable_attributes = array();

    protected function configure()
    {
        $this
            ->setName('product:create:dummy')
            ->addArgument('website-id', InputArgument::OPTIONAL, 'Website Id to create products (default: 1)')
            ->addArgument('attribute-set-id', InputArgument::OPTIONAL, 'Attribute Set Id (default: Default with ID 4)')
            ->addArgument('product-type', InputArgument::OPTIONAL, 'Product Type (default: simple)')
            ->addArgument('sku-prefix', InputArgument::OPTIONAL, 'Prefix for product\'s sku (default: MAGPROD-)')
            ->addArgument('category-ids', InputArgument::OPTIONAL, 'Categories for product association (comma separated - default null)')
            ->addArgument('product-status', InputArgument::OPTIONAL, 'Product Status (default: enabled)')
            ->addArgument('product-visibility', InputArgument::OPTIONAL, 'Product Visibility (default: visibile_both)')
            ->addArgument('product-number', InputArgument::OPTIONAL, 'Number of products to create')
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

        $this->input = $input;
        $this->output = $output;

        $this->output->writeln("<warning>This is experimental and it only create sample products.</warning>\r\n");

        // MANAGE ARGUMENTS
        $_argument = $this->manageArguments($input, $output);
        
        /**
         * LOOP to create products
         */ 
        for($i = 0; $i < $_argument['product-number']; $i++)
        {
            switch($_argument['product-type'])
            {
                // ******************
                // ** SIMPLE PRODUCT
                // ******************
                case \Mage_Catalog_Model_Product_Type::TYPE_SIMPLE:
                {
                    $this->createSimpleProduct($_argument, $i);
                    break;
                }
                // *************************
                // ** CONFIGURABLE PRODUCTS
                // *************************
                case \Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                {
                    $this->createConfigurableProduct($_argument, $i);
                    break;
                }
                case \Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
                {
                    break;
                }
                default:
                    $output->writeln("<error>INVALID PRODUCT TYPE</error>\r\n");
            }
        }
    }

    /**
     * Manage console arguments
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return array
     */
    protected function manageArguments(&$input, &$output)
    {
        /**
         * ARGUMENTS
         */
        $helper = $this->getHelper('question');
        $_argument = array();

        // WEBSITE ID
        if(is_null($input->getArgument('website-id'))) {
            $website_id = \Mage::getModel('core/website')->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('website_id', array('gt' => 0))
                ->setOrder('website_id', 'ASC');
            ;
            $_website_ids = array();

            foreach($website_id as $item)
            {
                $_website_ids[$item['website_id']] = $item['website_id']."|".$item['name'];
            }

            $question = new ChoiceQuestion(
                'Please select Website ID (default: 1)',
                $_website_ids,
                self::DEFAULT_WEBSITE_ID
            );
            $question->setErrorMessage('Website ID "%s" is invalid.');
            $response = explode("|", $helper->ask($input, $output, $question));
            $input->setArgument('website-id', $response[0]);
        }
        $output->writeln('<info>Website ID selected: '.$input->getArgument('website-id')."</info>\r\n");
        $_argument['website-id'] = $input->getArgument('website-id');

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
                self::DEFAULT_PRODUCT_ATTRIBUTE_SET_ID
            );
            $question->setErrorMessage('Attribute Set "%s" is invalid.');
            $response = explode("|", $helper->ask($input, $output, $question));
            $input->setArgument('attribute-set-id', $response[0]);
        }
        $output->writeln('<info>Attribute Set ID selected: '.$input->getArgument('attribute-set-id')."</info>\r\n");
        $_argument['attribute-set-id'] = $input->getArgument('attribute-set-id');

        // PRODUCT TYPE
        if(is_null($input->getArgument('product-type'))) {
            $question = new ChoiceQuestion(
                'Please select Magento Product type (default: simple)',
                array(\Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, \Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE),
                // TODO: create other type
                //array('configurable', 'grouped'),
                0
            );
            $question->setErrorMessage('Magento Product Type "%s" is invalid.');
            $input->setArgument('product-type', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>Product Type selected: '.$input->getArgument('product-type')."</info>\r\n");
        $_argument['product-type'] = $input->getArgument('product-type');

        // SKU PREFIX
        if(is_null($input->getArgument('sku-prefix'))) {
            $question = new Question("Please enter the product sku prefix (default MAGPROD-): ", "MAGPROD-");
            $input->setArgument('sku-prefix', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>SKU PREFIX: ' . $input->getArgument('sku-prefix')."</info>\r\n");
        $_argument['sku-prefix'] = $input->getArgument('sku-prefix');

        // CATEGORY IDS
        if(is_null($input->getArgument('category-ids'))) {
            $question = new Question("Please enter the category ids for product association (comma separated): ", null);
            $input->setArgument('category-ids', $helper->ask($input, $output, $question));
        }
        $output->writeln('<info>Category Ids choosed: ' . $input->getArgument('category-ids')."</info>\r\n");
        $_argument['category-ids'] = $input->getArgument('category-ids');

        // PRODUCT STATUS
        if(is_null($input->getArgument('product-status'))) {
            $_prod_status = array();
            $_prod_status[\Mage_Catalog_Model_Product_Status::STATUS_ENABLED] = \Mage_Catalog_Model_Product_Status::STATUS_ENABLED."|Enabled";
            $_prod_status[\Mage_Catalog_Model_Product_Status::STATUS_DISABLED] = \Mage_Catalog_Model_Product_Status::STATUS_DISABLED."|Disabled";

            $question = new ChoiceQuestion(
                'Please select Product Status (default: enabled)',
                $_prod_status,
                self::DEFAULT_PRODUCT_STATUS
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
                self::DEFAULT_PRODUCT_VISIBILITY
            );
            $question->setErrorMessage('Product Visibility "%s" is invalid.');
            $response = explode("|", $helper->ask($input, $output, $question));
            $input->setArgument('product-visibility', $response[0]);
        }
        $output->writeln('<info>Product Visibility selected: '.$input->getArgument('product-visibility')."</info>\r\n");
        $_argument['product-visibility'] = $input->getArgument('product-visibility');

        // NUMBER OF PRODUCTS
        if(is_null($input->getArgument('product-number'))) {
            $question = new Question("Please enter the number of products to create (default 1): ", 1);
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

        // ** ************************************
        // ** OPTIONS FOR CONFIGURABLE PRODUCTS - not really arguments (maybe options?)
        // ***************************************

        if($_argument['product-type'] == \Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            // NUMBER OF CONFIGURABLE ATTRIBUTES
            if(!isset($_argument['attribute-configurable-number']) || is_null($_argument['attribute-configurable-number'])) {
                $question = new Question("Please enter the NUMBER of configurable ATTRIBUTE to use (default 1): ", 1);
                $question->setValidator(function ($answer) {
                    $answer = (int)($answer);
                    if (!is_int($answer) || $answer <= 0) {
                        throw new \RuntimeException(
                            'Please enter an integer value or > 0'
                        );
                    }
                    return $answer;
                });
                $_argument['attribute-configurable-number'] = $helper->ask($this->input, $this->output, $question);
                $this->output->writeln('<info>Number of configurable attribute to use: ' . $_argument['attribute-configurable-number'] . "</info>\r\n");

                for ($k = 0; $k < $_argument['attribute-configurable-number']; $k++) {
                    $question = new Question("Please enter the n." . $k . " configurable ATTRIBUTE CODE to use (example: color): ", "");
                    $this->_configurable_attributes[] = strtolower($helper->ask($this->input, $this->output, $question));
                    $this->output->writeln("<info>CONFIGURABLE ATTRIBUTE n." . $k . " CHOOSED: " . $this->_configurable_attributes[$k] . "</info>\r\n");
                }
            }

            // NUMBER OF CHILDREN PRODUCTS
            if(!isset($_argument['product-children-number']) || is_null($_argument['product-children-number'])) {
                $question = new Question("Please enter the NUMBER of children for each configurable product (default 1): ", 1);
                $question->setValidator(function ($answer) {
                    $answer = (int)($answer);
                    if (!is_int($answer) || $answer <= 0) {
                        throw new \RuntimeException(
                            'Please enter an integer value or > 0'
                        );
                    }
                    return $answer;
                });
                $_argument['product-children-number'] = $helper->ask($this->input, $this->output, $question);
                $this->output->writeln('<info>Number of children to create: ' . $_argument['product-children-number'] . "</info>\r\n");
            }
        }

        
        return $_argument;
    }

    /**
     * Create simple product
     *
     * @param array $_argument
     * @param int $counter
     * @return false|\Mage_Catalog_Model_Product
     */
    protected function createSimpleProduct($_argument, $counter)
    {
        // Check if product exists
        // If yes, skip and increment counter
        $exist = true;
        $suffix = $counter;
        while($exist) {
            $sku = $_argument['sku-prefix'].$suffix;
            if ($this->checkIfProductExists($sku)) {
                $this->output->writeln("<comment>" . $counter . ") PRODUCT: WITH SKU: '" . $sku . "' EXISTS! Skip</comment>\r");
                $suffix++;
                continue;
            }
            else {
                $exist = false;
            }
        }

        // Creating product
        try {
            $product = \Mage::getModel('catalog/product');
            $product->setTypeId($_argument['product-type']);
            $product->setAttributeSetId($_argument['attribute-set-id']);
            $product->setWebsiteIds(array($_argument['website-id']));

            $product->setName(self::DEFAULT_PRODUCT_NAME." ".$sku);
            $product->setDescription(self::DEFAULT_PRODUCT_DESCRIPTION);
            $product->setShortDescription(self::DEFAULT_PRODUCT_SHORT_DESCRIPTION);
            $product->setSku($sku);
            $product->setWeight(rand(1, 99));
            $product->setStatus($_argument['product-status']);
            $product->setVisibility($_argument['product-visibility']);
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
                $_tmp_image[$count] = file_get_contents('http://lorempixel.com/600/600/?'.sha1($sku.$count));
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
            if(!is_null($_argument['category-ids']))
            {
                $_category_ids = explode(',', $_argument['category-ids']);
                $product->setCategoryIds($_category_ids);
            }

            $product->save();
        }
        catch(\Exception $e)
        {
            $this->output->writeln("<error>".$e->getMessage()."</error>");
            die;
        }

        // Check if product was created correctly
        if($this->checkIfProductExists($product->getSku()))
        {
            $this->output->writeln("<comment>".$counter.") PRODUCT: '" . $product->getName()."' WITH SKU: '".$product->getSku()."' CREATED!</comment>\r");
        }
        else {
            $this->output->writeln("<error>".$counter.") ERROR WITH PRODUCT: '" . $product->getName()."' SKU: '".$product->getSku()."' </error>\r\n");
        }

        return $product;
    }

    /**
     * Create configurable product
     *
     * @param array $_argument
     * @param int $counter
     * @return false|\Mage_Catalog_Model_Product
     * @throws \Exception
     */
    protected function createConfigurableProduct($_argument, $counter)
    {
        $_attributes_id = array();
        $configurableProductsData = array();
        $_links_attribute = array();

        // CREATE CHILDREN PRODUCTS
        for($q = 0; $q < $_argument['product-children-number']; $q++) {

            $_sku_child = $_argument['sku-prefix'] . "CHILD-";
            $_child_argument = array(
                'website-id' => $_argument['website-id'],
                'attribute-set-id' => $_argument['attribute-set-id'],
                'product-type' => \Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                'sku-prefix' => $_sku_child,
                'category-ids' => $_argument['category-ids'],
                'product-status' => \Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
                'product-visibility' => \Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
                'product-number' => 1
            );
            $product_child = $this->createSimpleProduct($_child_argument, $q);

            foreach ($this->_configurable_attributes as $child_attr) {
                $attributeInfo = \Mage::getResourceModel('eav/entity_attribute_collection')->setCodeFilter($child_attr)->getFirstItem();
                $attribute = \Mage::getModel('catalog/resource_eav_attribute')->load($attributeInfo->getAttributeId());
                $attributeOptions = $attribute->getSource()->getAllOptions(false);
                $product_child->setData($child_attr, $attributeOptions[$q]['value']);
                $product_child->getResource()->saveAttribute($product_child, $child_attr);

                // extra data
                $_attributes_id[] = $attributeInfo->getAttributeId();
                $_links_attribute[] = array(
                    'label' => $child_attr,
                    'attribute_id' => $attributeInfo->getAttributeId(),
                    'value_index' => $attributeOptions[$q]['value'],
                    'is_percent' => '0',
                    'pricing_value' => $product_child->getFinalPrice()
                );
            }

            $configurableProductsData[$product_child->getId()] = array(
                $_links_attribute
            );
        }

        // CREATE PARENT PRODUCT
        $_argument['sku-prefix'] = $_argument['sku-prefix'] . "PARENT-";

        // Check if product exists
        // If yes, skip and increment counter
        $exist = true;
        $suffix = $counter;
        while($exist) {
            $sku = $_argument['sku-prefix'].$suffix;
            if ($this->checkIfProductExists($sku)) {
                $this->output->writeln("<comment>" . $counter . ") PARENT PRODUCT: WITH SKU: '" . $sku . "' EXISTS! Skip</comment>\r");
                $suffix++;
                continue;
            }
            else {
                $exist = false;
            }
        }

        try {
            $product_parent = \Mage::getModel('catalog/product');
            $product_parent->setTypeId($_argument['product-type']);
            $product_parent->setAttributeSetId($_argument['attribute-set-id']);
            $product_parent->setWebsiteIds(array($_argument['website-id']));

            $product_parent->setName(self::DEFAULT_PRODUCT_NAME . " " . $sku);
            $product_parent->setDescription(self::DEFAULT_PRODUCT_DESCRIPTION);
            $product_parent->setShortDescription(self::DEFAULT_PRODUCT_SHORT_DESCRIPTION);
            $product_parent->setSku($sku);
            $product_parent->setWeight(rand(1, 99));
            $product_parent->setStatus($_argument['product-status']);
            $product_parent->setVisibility($_argument['product-visibility']);
            $product_parent->setPrice(rand(10, 999));
            $product_parent->setTaxClassId(0);

            // IMAGES
            if (!file_exists(\Mage::getBaseDir('media') . DS . "import/")) {
                mkdir(\Mage::getBaseDir('media') . DS . "import/", 0777, true);
            }
            $product_parent->setMediaGallery(array('images' => array(), 'values' => array()));
            $_tmp_image = array();
            $_file_image = array();
            for ($count = 0; $count < 3; $count++) {
                $_tmp_image[$count] = file_get_contents('http://lorempixel.com/600/600/?' . sha1($sku . $count));
                $_file_image[$count] = \Mage::getBaseDir('media') . DS . "import/" . $sku . "-" . sha1($sku . $count) . ".jpg";
                file_put_contents($_file_image[$count], $_tmp_image[$count]);
                $product_parent->addImageToMediaGallery($_file_image[$count], array('image', 'thumbnail', 'small_image'), false, false);
            }
            $product_parent->setStockData(array(
                    'is_in_stock' => 1,
                )
            );

            // CATEGORIES
            if (!is_null($_argument['category-ids'])) {
                $_category_ids = explode(',', $_argument['category-ids']);
                $product_parent->setCategoryIds($_category_ids);
            }

            // LINK FOR PARENT AND CHILD
            $product_parent->getTypeInstance()->setUsedProductAttributeIds(array_unique($_attributes_id));
            $configurableAttributesData = $product_parent->getTypeInstance()->getConfigurableAttributesAsArray();
            $product_parent->setCanSaveConfigurableAttributes(true);
            $product_parent->setConfigurableAttributesData($configurableAttributesData);
            $product_parent->setConfigurableProductsData($configurableProductsData);
            $product_parent->save();

            // Check if product was created correctly
            if($this->checkIfProductExists($product_parent->getSku()))
            {
                $this->output->writeln("<comment>" . $counter . ") PRODUCT: '" . $product_parent->getName() . "' WITH SKU: '" . $product_parent->getSku() . "' CREATED!</comment>\r");
            } else {
                $this->output->writeln("<error>" . $counter . ") ERROR WITH PRODUCT: '" . $product_parent->getName() . "' SKU: '" . $product_parent->getSku() . "' </error>\r\n");
            }
        }
        catch(\Exception $e)
        {
            $this->output->writeln("<error>".$e->getMessage()."</error>");
            die;
        }

        return $product_parent;
    }

    /**
     * Check if a product exists
     *
     * @param string $sku
     * @return bool
     */
    protected function checkIfProductExists($sku)
    {
        $collection = \Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToFilter('sku', array('eq' => $sku));
        $_size = $collection->getSize();
        if ($_size > 0)
        {
            return true;
        }
        else {
            return false;
        }
    }
}