<?php

declare(strict_types=1);

namespace Stsbl\SendMailAsGroupBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CrudBundle\Entity\CrudInterface;
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
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity="GroupMail", inversedBy="recipients", fetch="EAGER")
     * @ORM\JoinColumn(name="msg_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank()
     */
    private ?GroupMail $mail;

    /**
     * @ORM\Column(name="recipient", type="text", nullable=false)
     * @Assert\NotBlank()
     */
    private ?string $recipient;

    /**
     * @ORM\Column(name="recipient_display", type="text", nullable=false)
     * @Assert\NotBlank()
     */
    private ?string $recipientFullName;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return sprintf('%s <%s>', $this->recipientFullName, $this->recipient);
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /* Generated getters and setters */

    /**
     * @return $this
     */
    public function setRecipient(?string $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    /**
     * @return $this
     */
    public function setRecipientFullName(?string $recipientFullName): self
    {
        $this->recipientFullName = $recipientFullName;

        return $this;
    }

    public function getRecipientFullName(): ?string
    {
        return $this->recipientFullName;
    }

    /**
     * @return $this
     */
    public function setMail(?GroupMail $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getMail(): ?GroupMail
    {
        return $this->mail;
    }
}
