<?php

namespace Arsitm\Mvex\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MapCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('map')
            ->setDescription('make the specified requests to URLs by sitemap.xml')
            ->setHelp('This command sends the specified requests to the URL list for the sitemap.xml')
            ->addArgument('url', InputArgument::REQUIRED, 'The URL of a website sitemap.xml')
            ->addArgument('c', InputArgument::OPTIONAL, 'Concurrency', 1)
            ->addOption('headers', 'H', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Headers', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $array = [];
        $sitemap_url = $input->getArgument('url');
        $concurrency = $input->getArgument('c');
        $headers = $input->getOption('headers');

        $url_list = $this->getURLArray($sitemap_url);
        $number_of_requests = count($url_list);

        $output->writeln("Sending $number_of_requests requests with $concurrency Concurrency");
        $client = new Client([
            'on_stats' => function (TransferStats $stats) use (&$array) {
                $time = $stats->getTransferTime();
                $array[] = $time;
            },
        ]);

        $progress = new ProgressBar($output, $number_of_requests);

        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progress->start();
        $requests = function () use ($number_of_requests, $url_list, $headers) {
            for ($i = 0; $i < $number_of_requests; $i++) {
                yield new Request('GET', $url_list[$i], $headers);
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => $concurrency,
            'fulfilled'   => function ($response, $index) use ($progress) {
                $progress->advance();
            },
            'rejected' => function ($reason, $index) {
                // this is delivered each failed request
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
        $progress->finish();
        $output->writeln('');
        $output->writeln('Done!');
    }

    /**
     * @param $sitemap_url String URL of the sitemap.xml
     * @return array
     */
    protected function getURLArray($sitemap_url)
    {

        $client = new Client();
        $response = $client->get($sitemap_url);
        $sitemap_string = $response->getBody()->getContents();
        return $this->parseSitemap(simplexml_load_string($sitemap_string));
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return array
     */
    protected function parseSitemap(\SimpleXMLElement $xml)
    {

        $URLList = [];

        foreach ($xml as $xml_item) {
            if ($xml_item->loc) {
                $URLList[] = $xml_item->loc;
            }
        }

        return $URLList;
    }
}
