<?php

namespace Lle\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Lle\MediaBundle\Entity\File;
use Lle\MediaBundle\Entity\Folder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ReaderMediaCommand extends Command
{

    private $finder;
    private $em;
    private $directories;
    private $files;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->finder = new Finder();
        $this->em = $em;
        $this->directories = [];
        $this->files = [];
    }

    protected function configure()
    {
        $this
            ->setName('lle:media:reader')
            ->setDescription('Reade media directory and persist')
            ->setHelp('Reade media directory and persist')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->finder->in('media');
        $this->directories = $this->em->getRepository(Folder::class)->createQueryBuilder('f','f.name')->getQuery()->getResult();
        $this->files = $this->em->getRepository(File::class)->createQueryBuilder('f','f.path')->getQuery()->getResult();
        $this->readDirectories($output);
        $this->readFiles($output);

        $this->em->flush();
    }

    public function readDirectories(OutputInterface $output){
        $output->writeln('---------DIRECTORY----------');
        foreach($this->finder->directories() as $directory){
            $output->writeln($directory);
            /* @var SplFileInfo $directory */
            $ormDirectory = new Folder();
            $ormDirectory->setName((string)$directory);

            //In class SplFileInfo the path return the parent directory
            if(array_key_exists($directory->getPath(), $this->directories)){
                /* @var Folder $folder */
                $folder = $this->directories[$directory->getPath()];
                $ormDirectory->setParent($folder);
            }
            if(!array_key_exists((string)$directory, $this->directories)) {
                $this->em->persist($ormDirectory);
                $this->medias[(string)$directory] = $ormDirectory;
            }
        }
    }

    public function readFiles(OutputInterface $output){
        $output->writeln('---------FILE----------');
        foreach($this->finder->files() as $file){
            $output->writeln($file);
            /* @var SplFileInfo $file */
            $ormFile = new File();
            $ormFile->setPath((string)$file);
            $ormFile->setFilename($file->getFilename());
            $ormFile->setMimetype($file->getExtension());
            $ormFile->setSize($file->getSize());
            if(array_key_exists($file->getPath(), $this->directories)){
                /* @var Folder $folder */
                $folder = $this->directories[$file->getPath()];
                $ormFile->setFolder($folder);
            }
            if(!array_key_exists((string)$file, $this->files)) {
                $this->em->persist($ormFile);
                $this->medias[(string)$file] = $ormFile;
            }
        }
    }

}