<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;

class EmailAttachment
{
    const TYPE_ATTACHMENT       = 1; // oro attachment (OroAttachmentBundle)
    const TYPE_EMAIL_ATTACHMENT = 2; // email attachment
    const TYPE_UPLOADED         = 3; // new uploaded file

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var int
     */
    protected $fileSize;

    /**
     * @var string
     */
    protected $modified;

    /**
     * @var EmailAttachment
     */
    protected $emailAttachment;

    /**
     * @var UploadedFile
     */
    protected $file;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return EmailAttachmentEntity
     */
    public function getEmailAttachment()
    {
        return $this->emailAttachment;
    }

    /**
     * @param EmailAttachmentEntity $emailAttachment
     *
     * @return $this
     */
    public function setEmailAttachment($emailAttachment)
    {
        $this->emailAttachment = $emailAttachment;
        $this->setFileName($this->emailAttachment->getFileName());

        return $this;
    }

    /**
     * @param string $fileName
     *
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return (string) $this->fileName;
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @param int $fileSize
     *
     * @return $this
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return string
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param string $modified
     *
     * @return $this
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param UploadedFile $uploadedFile
     *
     * @return $this
     */
    public function setFile($uploadedFile)
    {
        $this->file = $uploadedFile;

        return $this;
    }

    /**
     * get string info
     * @return string
     */
    public function getInfo()
    {
        $info = '';
        if ($this->fileSize) {
            $info .= $this->humanFilesize($this->fileSize, 1);
        }
        if ($this->modified) {
            $info .= ', ' . $this->modified;
        }

        return $info;
    }

    /**
     * todo move to formatter
     *
     * @param     $bytes
     * @param int $dec
     *
     * @return string
     */
    protected function humanFilesize($bytes, $dec = 2)
    {
        $size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
}
