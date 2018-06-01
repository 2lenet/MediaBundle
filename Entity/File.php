<?php

/**
 * Description of Folder
 *
 * @author ACA
 */

namespace Lle\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * LnrChannel
 *
 * @ORM\Table(name="Lle_Media_File")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class File {

    /**
     * @ORM\PrePersist()
     */
    public function prePersist() {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->upload();
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate() {
        $this->updatedAt = new \DateTime();
        $this->upload();
    }

    public function __toString() {
        $path = '';
        foreach($this->getFolder()->getVirtualPath() as $folder_name){
            $path = $path.'/'.$folder_name;
        }
        return $path.'/'.$this->filename.'.'.$this->mimetype;
    }


    public function upload($copy = false)
    {
        /*// la propriété « file » peut être vide si le champ n'est pas requis
        if (null === $this->temp_name) {
            return;
        }
        $path = $this->getUploadRootDir().'/';
        if(!file_exists($path)){
            mkdir($path);
        }
        foreach($this->getFolder()->getVirtualPath() as $folder_name){
	    $folder_name = str_replace(' ', '-', $folder_name);
            $folder_name = preg_replace('/[^A-Za-z0-9\-]/', '', $folder_name);

            $path = $path.'/'.$folder_name;
            if(!file_exists($path)){
                mkdir($path);
            }
        }

        $chemin = $path;
        $this->filename = preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', $this->filename);
        $path= $chemin.'/'.$this->filename.'.'.$this->mimetype;
        $i=1;
        while(file_exists($path)){
            $this->filename = $this->filename.'_'.$i;
            $path= $chemin.'/'.$this->filename.'.'.$this->mimetype;
            $i++;
        }

        if($copy){
            copy ( $this->temp_name , $path );
        }else{
            move_uploaded_file($this->temp_name,$path);
        }

        $this->path = $path;
        $this->temp_name = null;*/

        // la propriété « file » peut être vide si le champ n'est pas requis
        if (null === $this->temp_name) {
            return;
        }
        $path = '';
        if(!file_exists($this->getUploadRootDir())){
            mkdir($this->getUploadRootDir());
        }
        foreach($this->getFolder()->getVirtualPath() as $folder_name){

            $folder_name = str_replace(' ', '-', $folder_name);
            $folder_name = preg_replace('/[^A-Za-z0-9\-]/', '', $folder_name);

            $path = $path.'/'.$folder_name;
            if(!file_exists($this->getUploadRootDir().$path)){
                mkdir($this->getUploadRootDir().$path);
            }
        }

        $chemin = $path;
        $this->filename = preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', $this->filename);
        $path= $chemin.'/'.$this->filename.'.'.$this->mimetype;
        $i=1;
        while(file_exists($this->getUploadRootDir().$path)){
            $this->filename = $this->filename.'_'.$i;
            $path= $chemin.'/'.$this->filename.'.'.$this->mimetype;
            $i++;
        }

        if($copy){
            copy ( $this->temp_name , $this->getUploadRootDir().$path );
        }else{
            move_uploaded_file($this->temp_name,$this->getUploadRootDir().$path);
        }

        $this->path = $path;
        $this->temp_name = null;
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($this->path && file_exists($this->getUploadRootDir().$this->path)) {
            unlink($this->getUploadRootDir().$this->path);
        }
    }

    public function isEnabled() {
        return $this->enabled;

    }

    function __construct() {

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
     * @ORM\Column(name="folderId", type="bigint", nullable=true)
     */
    private $folderId;

    /**
     * @ORM\ManyToOne(targetEntity="Folder", inversedBy="files")
     * @ORM\JoinColumn(name="folderId", referencedColumnName="id")
     */
    private $folder;

    /**
     * name of the file
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=100, nullable=false, options={"comment" = "file name"})
     */
    private $filename;

    /**
     * description of the file
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=100, nullable=false, options={"comment" = "file description"})
     */
    private $description;

    /**
     * the file is a link or not
     * @var string
     * 
     * @ORM\Column(name="islink", type="boolean", nullable=true, options={"comment" = "The file is a link or not"})
     */
    private $islink;

    /**
     * if islink -> http link
     * else path in files system
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=false, options={"comment" = "if islink -> http link else path in files system"})
     */
    private $path;

    /**
     * file mime type
     * @var string
     * 
     * @ORM\Column(name="mimetype", type="string", length=20, nullable=true)
     */
    private $mimetype;

    /**
     * file is enabled or not
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=true, options={"comment" = "", "default" = true})
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

    public function getFolderId() {
        return $this->folderId;
    }

    public function getFolder() {
        return $this->folder;
    }

    public function getFileName() {
        return $this->filename;
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

    public function setFolderId($folderId) {
        $this->folderId = $folderId;
    }

    public function setFolder($folder) {
        $this->folder = $folder;
    }

    public function setFilename($name) {
        $this->filename = $name;
    }

    public function setUpdatedAt(\DateTime $updatedAt) {
        $this->updatedAt = $updatedAt;
    }

    public function setCreatedAt(\DateTime $createdAt) {
        $this->createdAt = $createdAt;
    }


    public function setEnabled($enabled) {
        $this->enabled = $enabled;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->getUploadRootDir().$this->path;
    }

    /**
     * @param string $mimetype
     */
    public function setMimetype($mimetype)
    {
        $this->mimetype = $mimetype;
    }

    /**
     * @return string
     */
    public function getMimetype()
    {
        return $this->mimetype;
    }

    /**
     * @param string $islink
     */
    public function setIslink($islink)
    {
        $this->islink = $islink;
    }

    /**
     * @return string
     */
    public function getIslink()
    {
        return $this->islink;
    }


    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function getDisplayDesc(){
        $number = substr($this->description,0,2);
        if(is_numeric(trim($number))){
            return substr($this->description,2);
        }
        return $this->description;
    }

    protected function getUploadRootDir()
    {
        //@TODO le chemin absolu du répertoire où les documents uploadés doivent être sauvegardés
        return '/home/sites/clicom/UploadedFiles';
    }


    private $temp_name=null;

    /**
     * @param null $temp_name
     */
    public function setTempName($temp_name)
    {
        $this->temp_name = $temp_name;
    }

    /**
     * @return null
     */
    public function getTempName()
    {
        return $this->temp_name;
    }


    // to store whatever the file exist in file system or not (set after calling testFileExist function)
    private $file_exist = false;
    /**
     * @return boolean
     */
    public function getFileExist()
    {
        return $this->file_exist;
    }

    public function testFileExist(){
        if(file_exists($this->getUploadRootDir().$this->path)){
            $this->file_exist = true;
            return true;
        }
        return false;
    }

    public function changeOwner($owner){
        chown ( $this->getUploadRootDir().$this->path , $owner );
        chgrp( $this->getUploadRootDir().$this->path , $owner );
        $path = $this->getUploadRootDir();
        foreach($this->getFolder()->getVirtualPath() as $folder_name){
            $path = $path.'/'.$folder_name;
            chown ( $path , $owner );
            chgrp( $path , $owner );
        }
    }
    public function changePermission($per){
        chmod ( $this->getUploadRootDir().$this->path , $per );
        $path = $this->getUploadRootDir();
        foreach($this->getFolder()->getVirtualPath() as $folder_name){
            $path = $path.'/'.$folder_name;
            chmod ( $path , $per );
        }
        // ;
    }

    /**
     * type file
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=true, options={"comment" = "", "default" = 0})
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="File", inversedBy="files")
     * @ORM\JoinColumn(name="masterId", referencedColumnName="id")
     */
    private $master;

    /**
     * @ORM\OneToMany(targetEntity="File", mappedBy="master")
     */
    private $files;

    /**
     * @param mixed $files
     */
    public function setFiles($files)
    {
        $this->files = $files;
    }

    /**
     * @return mixed
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param mixed $master
     */
    public function setMaster($master)
    {
        $this->master = $master;
    }

    /**
     * @return mixed
     */
    public function getMaster()
    {
        return $this->master;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    public function getBase64(){
        if(file_exists($this->getUploadRootDir().$this->path)){
            $file = file_get_contents($this->getUploadRootDir().$this->path);
            return base64_encode($file);
        }
        return null;
    }

    public function getDisplayName(){
        $number = substr($this->filename,0,2);
        if(is_numeric(trim($number))){
            return substr($this->filename,2);
        }
        return $this->filename;
    }

    public function isAccessible(){

            if($this->folder){
                return $this->folder->isEnabled();
            }
            return true;

    }

}
