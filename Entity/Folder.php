<?php

/**
 * Description of Folder
 */

namespace Lle\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Util\Urlizer;
/**
 *
 * @Gedmo\Tree(type="nested")* 
 * @ORM\Table(name="lle_media_folder")
 * @ORM\Entity()
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * 
 */
class Folder {


    public function __toString() {
        return $this->getName() ? : '-';
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
     */
    private $children;

    /**
     * @ORM\Column(length=1024, nullable=true)
     */
    private $slug;

    /**
     * @ORM\Column(length=1024, nullable=true)
     */
    private $path;

     /**
     * @ORM\OneToMany(targetEntity="File", mappedBy="folder",cascade={"remove"})
     * @ORM\OrderBy({"filename" = "ASC"})
     */
    private $files;

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

    public function setFiles($files) {
        $this->files = $files;
    }

    public function getPath() {
        if (!$this->path) {
            $this->updatePath();
        }
        return $this->path;
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
}
