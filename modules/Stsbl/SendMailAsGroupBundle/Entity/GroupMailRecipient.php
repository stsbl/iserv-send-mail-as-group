<?php

namespace Stsbl\SendMailAsGroupBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CrudBundle\Entity\CrudInterface;
use Symfony\Component\Validator\Constraints as Assert;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
 * StsblSendMailAsGroupBundle:GroupMailRecipient
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/license/MIT>
 * @ORM\Entity
 * @ORM\Table(name="mail_send_as_group_log_recipient")
 */
class GroupMailRecipient implements CrudInterface
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
     * @ORM\ManyToOne(targetEntity="GroupMail", inversedBy="recipients", fetch="EAGER")
     * @ORM\JoinColumn(name="msg_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank()
     * 
     * @var GroupMail
     */
    private $mail;
    
    /**
     * @ORM\Column(name="recipient", type="text", nullable=false)
     * @Assert\NotBlank()
     * 
     * @var string
     */
    private $recipient;

    /**
     * @ORM\Column(name="recipient_display", type="text", nullable=false)
     * @Assert\NotBlank()
     * 
     * @var string
     */
    private $recipientFullName;
    
    /**
     * {@inheritdoc}
     */
    public function __toString() 
    {
        return sprintf('%s <%s>', $this->recipientFullName, $this->recipient);
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

    /* Generated getters and setters */
    
    /**
     * Set recipient
     *
     * @param string $recipient
     *
     * @return GroupMailRecipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Get recipient
     *
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Set recipientFullName
     *
     * @param string $recipientFullName
     *
     * @return GroupMailRecipient
     */
    public function setRecipientFullName($recipientFullName)
    {
        $this->recipientFullName = $recipientFullName;

        return $this;
    }

    /**
     * Get recipientFullName
     *
     * @return string
     */
    public function getRecipientFullName()
    {
        return $this->recipientFullName;
    }

    /**
     * Set mail
     *
     * @param GroupMail $mail
     *
     * @return GroupMailRecipient
     */
    public function setMail(GroupMail $mail = null)
    {
        $this->mail = $mail;

        return $this;
    }

    /**
     * Get mail
     *
     * @return GroupMail
     */
    public function getMail()
    {
        return $this->mail;
    }
}
