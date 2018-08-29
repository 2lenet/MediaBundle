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
use Lle\MediaBundle\Lib\MimeReader;

class ReaderMediaCommand extends Command
{

    private $finder;
    private $em;
    private $directories;
    private $root;
    private $files;
    private $counter;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->finder = new Finder();
        $this->em = $em;
        $this->directories = [];
        $this->files = [];
        $this->counter = ['file'=>0,'folder'=>0];
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
        $this->root = $this->em->getRepository(Folder::class)->findOneByParent(null);
        $this->directories = $this->em->getRepository(Folder::class)->createQueryBuilder('f','f.path')->getQuery()->getResult();
        $this->files = $this->em->getRepository(File::class)->createQueryBuilder('f','f.path')->getQuery()->getResult();
        $this->readDirectories($output);
        $this->readFiles($output);

        $output->writeln('Wait');
        $this->em->flush();
        $output->writeln('<fg=green>New Folder: ' . $this->counter['folder'] .'</>');
        $output->writeln('<fg=green>New File: ' . $this->counter['file'] .' </>');
    }

    public function readDirectories(OutputInterface $output){
        $output->writeln('---------DIRECTORY----------');
        foreach($this->finder->directories() as $directory){
            $output->writeln($directory);
            /* @var SplFileInfo $directory */
            $ormDirectory = new Folder();
            $fullPath = str_replace('media/','',(string)$directory);
            $path = str_replace('media/','',(string)$directory->getPath());

            $ormDirectory->setName($directory->getFilename());
            $ormDirectory->setPath($fullPath);

            //In class SplFileInfo the path return the parent directory
            if(array_key_exists($path, $this->directories)){
                /* @var Folder $folder */
                $folder = $this->directories[$path];
                $ormDirectory->setParent($folder);
            } else {
                $ormDirectory->setParent($this->root);
            }
            if(!array_key_exists($fullPath, $this->directories)) {
                $this->em->persist($ormDirectory);
                $this->counter['folder']++;
                $this->directories[$fullPath] = $ormDirectory;
            }
        }
    }

    public function readFiles(OutputInterface $output){
        $output->writeln('---------FILE----------');
        foreach($this->finder->files() as $file){
            $output->writeln($file);
            /* @var SplFileInfo $file */
            $ormFile = new File();
            $fullPath = str_replace('media/','',(string)$file);
            $path = str_replace('media/','',(string)$file->getPath());
            $ormFile->setPath($fullPath);
            $ormFile->setFilename($file->getFilename());
            $mime = new MimeReader($file->getRealPath());
            $ormFile->setMimetype($mime->getType());
            $ormFile->setSize($file->getSize());
            if(array_key_exists($path, $this->directories)){
                /* @var Folder $folder */
                $folder = $this->directories[$path];
                $ormFile->setFolder($folder);
            }
            if(!array_key_exists($fullPath, $this->files)) {
                $this->em->persist($ormFile);
                $this->counter['file']++;
                $this->files[$fullPath] = $ormFile;
            }
        }
    }

}