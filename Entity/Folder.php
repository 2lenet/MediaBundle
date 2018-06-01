<?php

/**
 * Description of Folder
 *
 * @author ACA
 */

namespace Lle\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LnrChannel
 *
 * @ORM\Table(name="Lle_Media_Folder")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class Folder {

    public function __construct() {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->status = 0;
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist() {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate() {
        $this->updatedAt = new \DateTime();
    }

    public function __toString() {
        return $this->getName() ? : '-';
    }


    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="parentId", type="bigint", nullable=true)
     */
    private $parentId;

    /**
     * @ORM\ManyToOne(targetEntity="Folder", inversedBy="children")
     * @ORM\JoinColumn(name="parentId", referencedColumnName="id")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Folder", mappedBy="parent",cascade={"remove"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="File", mappedBy="folder",cascade={"remove"})
     * @ORM\OrderBy({"filename" = "ASC"})
     */
    private $files;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * folder is enabled or not
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=true, options={"comment" = "", "default" = 0})
     */
    private $enabled;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="date", nullable=true)
     */
    private $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="date", nullable=true)
     */
    private $createdAt;

    public function getId() {
        return $this->id;
    }

    public function getParentId() {
        return $this->parentId;
    }

    public function getParent() {
        return $this->parent;
    }

    public function getChildren() {
        return $this->children;
    }

    public function getName() {
        return $this->name;
    }

    public function isEnabled() {
        if ($this->enabled) {
            if ($this->parent) {
                return $this->parent->isEnabled();
            }
            return true;
        } else
            return false;
    }

    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setParentId($parentId) {
        $this->parentId = $parentId;
    }

    public function setParent($parent) {
        $this->parent = $parent;
    }

    public function setChildren($children) {
        $this->children = $children;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setUpdatedAt(\DateTime $updatedAt) {
        $this->updatedAt = $updatedAt;
    }

    public function setCreatedAt(\DateTime $createdAt) {
        $this->createdAt = $createdAt;
    }

    public function getFiles() {
        return $this->files;
    }

    public function setFiles($files) {
        $this->files = $files;
    }

    public function setEnabled($status){
        $this->enabled = $status;
    }
    public function getEnabled(){
        return $this->enabled ;
    }

    public function getVirtualPath(){
        if($this->id){
            if($this->parent){
                $retour = $this->parent->getVirtualPath();
                $retour[$this->id] = $this->name;
                return $retour;
            }else{
                $path[$this->id] = $this->name;
                return $path;
            }
        }else{
            // racine id == null
            return array();
        }

    }

    public function getDisplayName(){
        $number = substr($this->name,0,2);
        if(is_numeric($number)){
            $name = preg_replace( "~\s*[0-9]+\s*_\s*(.*)~" , "$1", $this->name);
            return $name;

            return substr($this->name,3);
        }



        return $this->name;
    }

    public function getArchiveName( ){
        $name = preg_replace( '~^[0-9]{1,2}_\s~i', "", $this->getName() );
        $name = preg_replace( '~[^a-z0-9]~i', "-", $name );

        return 'bubendorff' . $this->getId() . '_' . $name . '.zip';
    }

}
