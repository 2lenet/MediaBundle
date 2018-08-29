<?php

/**
 * Description of Folder
 */

namespace Lle\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Util\Urlizer;
/**
 *
 * @Gedmo\Tree(type="nested")* 
 * @ORM\Table(name="lle_media_folder",indexes={@ORM\Index(name="folder_path_index", columns={"path"})})
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * 
 */
class Folder {

    public function __toString() {
        return $this->getPath() ? : '-';
    }

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private $name;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(type="integer")
     */
    private $lvl;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="Folder")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Folder", inversedBy="children")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Folder", mappedBy="parent")
     * @ORM\OrderBy({"name" = "ASC"})* 
     */
    private $children;

    /**
     * @ORM\Column(length=1024, type="string", nullable=true)
     */
    private $slug;

    /**
     * @ORM\Column(length=512, type="string", nullable=true)
     */
    private $path;

    /**
     * @ORM\Column(type="boolean", nullable=false,  options={"default" : 0})
     */
    private $public = false;

     /**
     * @ORM\OneToMany(targetEntity="File", orphanRemoval=true, mappedBy="folder",cascade={"remove"})
     * @ORM\OrderBy({"filename" = "ASC"})
     */
    private $files;

    public function __construct(){
        $this->files = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSlug()
    {
        return $this->slug;
    }
    
    public function setSlug()
    {
        return $this->slug;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function setParent(Folder $parent)
    {
        $this->parent = $parent;
    }

    public function getParents()
    {
        if ($this->getParent()) {
            $parents = $this->getParent()->getParents();
            $parents[] = $this->getParent();
            return $parents;
        } else {
            return [];
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getFiles() {
        return $this->files;
    }

    public function hasFile($filename){
        foreach($this->files as $file){
            /* @var File $file */
            if($file->getFileName() === $filename){
                return true;
            }
        }
        return false;
    }

    public function setFiles($files) {
        $this->files = $files;
    }

    public function addFile(File $file){
        $file->setFolder($this);
        $this->files->add($file);
    }
    public function removeFile(File $file)
    {
        $this->files->removeElement($file);
    }


    public function getPath() {
        if (!$this->path) {
            $this->updatePath();
        }
        return $this->path;
    }

    public function setPath($path){
        $this->path = $path;
    }

    public function updatePath() {
        $path = "/";
        $urlizer = new Urlizer();
        foreach($this->getParents() as $parent) {
            $path .= $urlizer->urlize($parent->getName()).'/';
        }
        $path .= $urlizer->urlize($this->getName()).'/';
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * @param mixed $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    public function getPublicPath(){
        if($this->getPublic()) {
            return '/media/' . $this->getPath();
        }
        return null;
    }

    public function getPublicPathFile($filename){
        //if($this->getPublic()) {
            foreach ($this->files as $file) {
                /* @var File $file */
                if ($file->getFileName() === $filename) {
                    return $file->getPublicPath();
                }
            }
        //}
        return null;
    }

    public function getLvl(){
        return $this->lvl;
    }





}
