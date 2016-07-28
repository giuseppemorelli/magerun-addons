<?php

namespace N98\Magento\Command\Category\Create;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class DummyCommandTest extends TestCase
{
    /**
     * outputBuffering
     */
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DummyCommand());
        $command = $this->getApplication()->find('category:create:dummy');
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            array(
                'command'                    => $command->getName(),
                'store-id'                   => 1,
                'children-categories-number' => 1,
                'category-name-prefix'       => 'My Awesome Category',
                'category-number'            => 1
            )
        );

        $this->testmanageArguments();

        $this->assertRegExp('/CATEGORY: \'My Awesome Category (.+)\' WITH ID: \'(.+)\' CREATED!/', $commandTester->getDisplay());
        $this->assertRegExp('/CATEGORY CHILD: \'My Awesome Category (.+)\' WITH ID: \'(.+)\' CREATED!/', $commandTester->getDisplay());

        // Check if the category is created correctly
        $match_parent = "";
        $match_child = "";
        preg_match('/CATEGORY: \'My Awesome Category (.+)\' WITH ID: \'(.+)\' CREATED!/', $commandTester->getDisplay(), $match_parent);
        $this->assertTrue($this->checkifCategoryExist($match_parent[2]));
        preg_match('/CATEGORY CHILD: \'My Awesome Category (.+)\' WITH ID: \'(.+)\' CREATED!/', $commandTester->getDisplay(), $match_child);
        $this->assertTrue($this->checkifCategoryExist($match_child[2]));

        // Delete category created
        $this->deleteMagentoCategory($match_parent[2]);
        $this->deleteMagentoCategory($match_child[2]);
    }

    protected function checkifCategoryExist($_category_id)
    {
        if(!is_null(\Mage::getModel('catalog/category')->load($_category_id)->getName())) {
            return true;
        }
    }

    protected function deleteMagentoCategory($_category_id)
    {
        \Mage::getModel('catalog/category')->load($_category_id)->delete();
    }

    /**
     * @outputBuffering
     */
    public function testmanageArguments()
    {
        $application = $this->getApplication();
        $application->add(new DummyCommand());
        $command = $this->getApplication()->find('category:create:dummy');
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            array(
                'command'                    => $command->getName(),
                'store-id'                   => 1,
                'children-categories-number' => 0,
                'category-name-prefix'       => 'My Awesome Category',
                'category-number'            => 0
            )
        );

        $arguments = $commandTester->getInput()->getArguments();
        $this->assertArrayHasKey('store-id', $arguments);
        $this->assertArrayHasKey('children-categories-number', $arguments);
        $this->assertArrayHasKey('category-name-prefix', $arguments);
        $this->assertArrayHasKey('category-number', $arguments);

        $this->assertTrue(is_integer($arguments['store-id']));
        $this->assertTrue(is_integer($arguments['children-categories-number']));
        $this->assertTrue(is_string($arguments['category-name-prefix']));
        $this->assertTrue(is_integer($arguments['category-number']));
    }
}
