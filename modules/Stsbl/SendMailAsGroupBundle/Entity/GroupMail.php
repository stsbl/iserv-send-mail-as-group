<?php

declare(strict_types=1);

namespace Stsbl\SendMailAsGroupBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use IServ\CoreBundle\Entity\Group;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\Library\Zeit\Zeit;
use Symfony\Component\Validator\Constraints as Assert;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
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
     */
    private ?int $id;

    /**
     * @ORM\Column(name="msg_title", type="text", nullable=false)
     * @Assert\NotBlank()
     */
    private ?string $messageTitle;

    /**
     * @ORM\ManyToOne(targetEntity="\IServ\CoreBundle\Entity\Group", fetch="EAGER")
     * @ORM\JoinColumn(name="sender", referencedColumnName="act", onDelete="CASCADE")
     * @Assert\NotBlank()
     */
    private ?Group $sender;

    /**
     * @ORM\Column(name="msg_body", type="text", nullable=false)
     * @Assert\NotBlank()
     */
    private ?string $messageBody;

    /**
     * @ORM\Column(name="time", type="datetimetz_immutable", nullable=false)
     * @Assert\NotBlank()
     */
    private \DateTimeImmutable $date;

    /**
     * @ORM\OneToMany(targetEntity="GroupMailRecipient", mappedBy="mail")
     *
     * @var GroupMailRecipient[]&Collection
     */
    private Collection $recipients;

    /**
     * @ORM\OneToMany(targetEntity="GroupMailFile", mappedBy="mail")
     *
     * @var GroupMailFile[]&Collection
     */
    private Collection $files;

    public function __construct()
    {
        $this->recipients = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->date = Zeit::now();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getMessageTitleDisplay();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessageTitleDisplay(): string
    {
        if ($this->messageTitle !== null && $this->messageTitle !== '') {
            return $this->messageTitle;
        }

        return _('(No subject)');
    }

    /* Generated getters and setters */

    /**
     * @return $this
     */
    public function setMessageTitle(?string $messageTitle): self
    {
        $this->messageTitle = $messageTitle;

        return $this;
    }

    public function getMessageTitle(): ?string
    {
        return $this->messageTitle;
    }

    /**
     * @return $this
     */
    public function setSender(?Group $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getSender(): ?Group
    {
        return $this->sender;
    }

    public function setMessageBody(?string $messageBody): self
    {
        $this->messageBody = $messageBody;

        return $this;
    }

    public function getMessageBody(): ?string
    {
        return $this->messageBody;
    }

    /**
     * Get date
     */
    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * @return $this
     */
    public function addRecipient(GroupMailRecipient $recipient): self
    {
        $this->recipients->add($recipient);

        return $this;
    }

    /**
     * @return $this
     */
    public function removeRecipient(GroupMailRecipient $recipient): self
    {
        $this->recipients->removeElement($recipient);

        return $this;
    }

    /**
     * Get recipients
     *
     * @return GroupMailRecipient[]&Collection
     */
    public function getRecipients(): Collection
    {
        return $this->recipients;
    }

    /**
     * @return $this
     */
    public function addFile(GroupMailFile $file): self
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeFile(GroupMailFile $file): self
    {
        $this->files->removeElement($file);

        return $this;
    }

    /**
     * @return GroupMailFile[]&Collection
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }
}
