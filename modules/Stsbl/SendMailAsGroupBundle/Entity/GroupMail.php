<?php

namespace Stsbl\SendMailAsGroupBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use IServ\CoreBundle\Entity\Group;
use IServ\CrudBundle\Entity\CrudInterface;
use Symfony\Component\Validator\Constraints as Assert;

/*
 * The MIT License
 *
 * Copyright 2019 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * StsblSendMailAsGroupBundle:GroupMail
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/license/MIT>
 * @ORM\Entity
 * @ORM\Table(name="mail_send_as_group_log")
 */
class GroupMail implements CrudInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     * @var integer
     */
    private $id;
    
    /**
     * @ORM\Column(name="msg_title", type="text", nullable=false)
     * @Assert\NotBlank()
     * 
     * @var string
     */
    private $messageTitle;
    
    /**
     * @ORM\ManyToOne(targetEntity="\IServ\CoreBundle\Entity\Group", fetch="EAGER")
     * @ORM\JoinColumn(name="sender", referencedColumnName="act", onDelete="CASCADE")
     * @Assert\NotBlank()
     * 
     * @var Group
     */
    private $sender;
    
    /**
     * @ORM\Column(name="msg_body", type="text", nullable=false)
     * @Assert\NotBlank()
     * 
     * @var string
     */
    private $messageBody;
    
    /**
     * @ORM\Column(name="time", type="datetime", nullable=false)
     * @Assert\NotBlank()
     * 
     * @var \DateTime
     */
    private $date;
    
    /**
     * @ORM\OneToMany(targetEntity="GroupMailRecipient", mappedBy="mail")
     * 
     * @var ArrayCollection
     */
    private $recipients;

    /**
     * @ORM\OneToMany(targetEntity="GroupMailFile", mappedBy="mail")
     * 
     * @var ArrayCollection
     */
    private $files;
    
    /**
     * {@inheritdoc}
     */
    public function __toString() 
    {
        return $this->getMessageTitleDisplay();
    }
    
    /**
     * Get id
     * 
     * @return integer
     */
    public function getId() 
    {
        return $this->id;
    }
    
    /**
     * Gets a displayable title
     * 
     * @return string
     */
    public function getMessageTitleDisplay()
    {
        if (!empty($this->messageTitle)) {
            return $this->messageTitle;
        } else {
            return _('(No subject)');
        }
    }

    /* Generated getters and setters */
    
    /**
     * Set messageTitle
     *
     * @param string $messageTitle
     *
     * @return GroupMail
     */
    public function setMessageTitle($messageTitle)
    {
        $this->messageTitle = $messageTitle;

        return $this;
    }

    /**
     * Get messageTitle
     *
     * @return string
     */
    public function getMessageTitle()
    {
        return $this->messageTitle;
    }

    /**
     * Set sender
     *
     * @param Group $sender
     *
     * @return GroupMail
     */
    public function setSender(Group $sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Get sender
     *
     * @return Group
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set messageBody
     *
     * @param string $messageBody
     *
     * @return GroupMail
     */
    public function setMessageBody($messageBody)
    {
        $this->messageBody = $messageBody;

        return $this;
    }

    /**
     * Get messageBody
     *
     * @return string
     */
    public function getMessageBody()
    {
        return $this->messageBody;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return GroupMail
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
    
    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->recipients = new ArrayCollection();
    }

    /**
     * Add recipient
     *
     * @param GroupMailRecipient $recipient
     *
     * @return GroupMail
     */
    public function addRecipient(GroupMailRecipient $recipient)
    {
        $this->recipients[] = $recipient;

        return $this;
    }

    /**
     * Remove recipient
     *
     * @param GroupMailRecipient $recipient
     */
    public function removeRecipient(GroupMailRecipient $recipient)
    {
        $this->recipients->removeElement($recipient);
    }

    /**
     * Get recipients
     *
     * @return ArrayCollection
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * Add file
     *
     * @param GroupMailFile $file
     *
     * @return GroupMail
     */
    public function addFile(GroupMailFile $file)
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * Remove file
     *
     * @param \Stsbl\SendMailAsGroupBundle\Entity\GroupMailFile $file
     */
    public function removeFile(GroupMailFile $file)
    {
        $this->files->removeElement($file);
    }

    /**
     * Get files
     *
     * @return ArrayCollection
     */
    public function getFiles()
    {
        return $this->files;
    }
}
