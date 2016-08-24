<?php

namespace GMdotnet\Magento\Command\Database\Maintain;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use N98\Magento\Command\Database\AbstractDatabaseCommand;

class CleanCommand extends AbstractDatabaseCommand
{
    private $tables = array('dataflow_batch_export', 'dataflow_batch_import', 'log_customer', 'log_quote',
                            'log_summary', 'log_summary_type', 'log_url', 'log_url_info', 'log_visitor',
                            'log_visitor_info', 'log_visitor_online', 'report_event', 'report_viewed_product_index');

    protected function configure()
    {
        $this->setName('db:maintain:clean-table')
             ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
             ->setDescription('Clean (truncate mysql command) all tables that are used only for statistics or log. [gmdotnet]');

        $help = <<<HELP
This command clean all tables in Magento Database with log data.
There are the tables:
- log_visitor
- dataflow_batch_export
- dataflow_batch_import
- log_customer
- log_quote
- log_summary
- log_summary_type
- log_url
- log_url_info
- log_visitor
- log_visitor_info
- log_visitor_online
- report_event
- report_viewed_product_index
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);
        $dbHelper = $this->getHelper('database');
        $dialog = $this->getHelper('dialog');

        if($input->getOption('force')) {
            $shouldDrop = true;
        }
        else {
            $shouldDrop = $dialog->askConfirmation($output, '<question>Really clean database tables?</question> <comment>[n]</comment>: ', false);
        }

        if($shouldDrop) {
            $query = "";
            foreach($this->tables as $table) {
                $query .= 'TRUNCATE TABLE `'.$table.'`; ';
                $output->writeln(sprintf("<comment>Preparing database table for cleaning: '%s'</comment>", $table));
            }

            $dbHelper->getConnection()->query($query);
            $output->writeln('<info>Clean database tables done!</info>');
        }
    }
}
