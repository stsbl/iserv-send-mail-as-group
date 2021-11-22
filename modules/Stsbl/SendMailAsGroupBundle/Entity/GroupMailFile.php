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
 * @ORM\Table(name="mail_send_as_group_log_files")
 */
class GroupMailFile implements CrudInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="GroupMail", inversedBy="files", fetch="EAGER")
     * @ORM\JoinColumn(name="msg_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank()
     *
     * @var GroupMail
     */
    private $mail;

    /**
     * @ORM\Column(name="mime", type="text", nullable=false)
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $mime;

    /**
     * @ORM\Column(name="name", type="text", nullable=false)
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $name;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->name ?? '?';
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the size in kb of the stored file
     */
    public function getSize(): int
    {
        return (int)str_replace(',', '.', (string)(filesize(sprintf('/var/lib/stsbl/send-mail-as-group/mail-files/%s-%s', $this->id, $this->name)) / 1000));
    }

    /* Generated getters and setters */

    /**
     * @return $this
     */
    public function setMime(?string $mime): self
    {
        $this->mime = $mime;

        return $this;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    /**
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setMail(GroupMail $mail = null): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getMail(): ?GroupMail
    {
        return $this->mail;
    }
}
