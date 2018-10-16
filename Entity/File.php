<?php

/**
 * Description of Folder
 */

namespace Lle\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * @ORM\Table(name="lle_media_file",indexes={@ORM\Index(name="file_path_index", columns={"path"})})
 * @ORM\Entity
 */
class File {


    public function __toString() {
        $path = '';
        foreach($this->getFolder()->getVirtualPath() as $folder_name){
            $path = $path.'/'.$folder_name;
        }
        return $path.'/'.$this->filename.'.'.$this->mimetype;
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
     * @ORM\ManyToOne(targetEntity="Folder", inversedBy="files", cascade={"persist"})
     * @ORM\JoinColumn(name="folder_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $folder;

    /**
     * @ORM\Column(name="path", type="string", length=512)
     */
    private $path;

    /**
     * name of the file
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=100, nullable=false)
     */    
    private $filename;

    /**
     * description of the file
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=100, nullable=true)
     */
    private $description;

    /**
     * file mime type
     * @var string
     * 
     * @ORM\Column(name="mimetype", type="string", length=50, nullable=true)
     */
    private $mimetype;

    /**
     * @ORM\Column(name="size", type="decimal")
     */
    private $size;

    public function getId() {
        return $this->id;
    }

    public function getFolder() {
        return $this->folder;
    }

    public function getFileName() {
        return $this->filename;
    }

    public function setFolder($folder) {
        $this->folder = $folder;
    }

    public function setFilename($name) {
        $this->filename = $name;
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
        return $this->path;
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

    protected function getUploadRootDir($root)
    {
        return $root.$this->folder->getPath().'/';
    }

    function getStoreFilename($filename)
    {
        return $filename;
    }

    public function upload($media, $root)
    {
        if ($media->isValid()) {
            $filename = $media->getClientOriginalName();
            $this->setFilename($filename);
            $storename = $this->getStoreFilename($filename);

            $this->setSize($media->getSize());
            $this->setMimeType($media->getMimetype());

            $upload_dir = $this->getUploadRootDir($root);

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0775, true);
            }
            $media->move(
                $upload_dir, $storename
            );

            $this->setPath($this->folder->getPath().'/'.$storename);
        }
    }


    /**
     * Get the value of size
     */ 
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set the value of size
     *
     * @return  self
     */ 
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    public function getPublicPath(){
        return '/media/'.$this->getPath();
    }
}
