<?php

namespace Domain\SiteBundle\Command;

use Oxa\VideoBundle\Entity\VideoMedia;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VideoConverterCommand extends ContainerAwareCommand
{
    protected $em;

    protected function configure()
    {
        $this->setName('data:video:convert');
        $this->setDescription('Converting video files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('domain_site.cron.logger');
        $logger->addInfo($logger::VIDEO_CONVERT, $logger::STATUS_START, 'execute:start');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $videoMapping = $this->em->getRepository('OxaVideoBundle:VideoMedia')->getConvertVideos(VideoMedia::VIDEO_STATUS_PENDING);
        $videoManager = $this->getContainer()->get('oxa.manager.video');

        $batchSize = 20;
        $i = 0;

        foreach ($videoMapping as $row) {
            /* @var $videoMedia VideoMedia */
            $videoMedia = $row[0];
            $output->writeln('Video id '. $videoMedia->getId() . ' is converting now');
            $videoMedia = $videoManager->convertVideoMedia($videoMedia);
            $this->em->persist($videoMedia);

            if (($i % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
            }

            $i ++;
        }

        $this->em->flush();
        $this->em->clear();
    }
}