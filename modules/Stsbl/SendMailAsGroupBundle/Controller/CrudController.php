<?php

declare(strict_types=1);

namespace Stsbl\SendMailAsGroupBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use IServ\Bundle\Autocomplete\Domain\AutocompleteType;
use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;
use IServ\Bundle\Autocomplete\Form\Type\AutocompleteTagsType;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\GroupFlag;
use IServ\CoreBundle\Service\User\UserStorageInterface;
use IServ\CrudBundle\Controller\StrictCrudController as BaseCrudController;
use IServ\FilesystemBundle\Model\File;
use IServ\FilesystemBundle\Upload\Form\Type\UniversalFileType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Stsbl\SendMailAsGroupBundle\Entity\GroupMail;
use Stsbl\SendMailAsGroupBundle\Entity\GroupMailFile;
use Stsbl\SendMailAsGroupBundle\Security\Privilege;
use Stsbl\SendMailAsGroupBundle\Service\SendMailAsGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

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
 * Controller for pages for composing new mails with a group as sender
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class CrudController extends BaseCrudController
{
    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function indexAction(Request $request)
    {
        $ret = parent::indexAction($request);
        $ret['compose_form'] = $this->getForm()->createView();

        return $ret;
    }

    /**
     * Sends an e-mail in background
     *
     * @Route("/groupmail/send", name="group_mail_send", options={"expose": true}, methods={"POST"})
     * @Security("is_granted('PRIV_MAIL_SEND_AS_GRP')")
     */
    public function sendAction(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException('Only XML HTTP requests are supported.');
        }

        $form = $this->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fwdIp = preg_replace("/.*,\s*/", "", $request->server->get('HTTP_X_FORWARDED_FOR', ''));
            $ret = $this->sendMail($form->getData(), $request->getClientIp(), $fwdIp);

            $errors = $ret['errors'];
            $success = $ret['success'];
            $exitCode = $ret['exitcode'];
            $messages = [];

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    if (!empty($error)) {
                        $messages[] = ['type' => 'error', 'message' => $error];
                    }
                }
            }

            if (count($success) > 0) {
                foreach ($success as $s) {
                    if (!empty($s)) {
                        $messages[] = ['type' => 'success', 'message' => $s];
                    }
                }
            }

            if ($exitCode === 0) {
                $result = 'success';
            } else {
                // perl usually returns an exit code other than 0 on errors
                $result = 'failed';
            }

            return new JsonResponse(['result' => $result, 'messages' => $messages]);
        }

        $jsonErrors = [];
        $errors = $form->getErrors(true);

        if ($errors->count() < 1) {
            $jsonErrors[] = [
                'type' => 'error',
                'message' => _('Unexcpected error during sending e-mail. Please try again.')
            ];
        } else {
            foreach ($errors as $error) {
                $message = $error->getMessage();

                if (!empty($message)) {
                    $jsonErrors[] = ['type' => 'error', 'message' => _($message)];
                }
            }
        }

        return new JsonResponse(['result' => 'failed', 'messages' => $jsonErrors]);
    }

    /**
     * Check if a group has the mail_ext privilege
     *
     * @Security("is_granted('PRIV_MAIL_SEND_AS_GRP')")
     * @Route("groupmail/mailext", name="group_mail_lookup_priv", options={"expose": true}, methods={"GET"})
     */
    public function lookupPrivAction(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Get group act and type from request
        $groupAct = $request->query->get('group');
        $type = $request->query->get('type');
        $ret = null;

        if (empty($groupAct)) {
            throw new \InvalidArgumentException('group should not be empty.');
        }

        if ($type !== 'priv' && $type !== 'flag' && $type !== 'flag_internal') {
            throw new \InvalidArgumentException(
                sprintf('type should be priv, flag_internal or flag, "%s" given.', $type)
            );
        }

        $er = $this->getDoctrine()->getRepository(Group::class);

        if ($type === 'priv') {
            $groups = $er->findByPrivilege('PRIV_MAIL_EXT');
        } elseif ($type === 'flag') {
            $groups = $er->findByFlag('mail_ext');
        } elseif ($type === 'flag_internal') {
            // Support for stsbl-iserv-mail-config
            // Only enable it if the flag exists
            $fr = $em->getRepository(GroupFlag::class);
            $flag = $fr->find('mail_int');

            // skip if not supported
            if (null === $flag) {
                $ret = true;
                goto response;
            } else {
                $groups = $er->findByFlag('mail_int');
            }
        }

        if (empty($groups)) {
            throw $this->createNotFoundException('No groups found!');
        }

        foreach ($groups as $group) {
            if ($group->getAccount() === $groupAct) {
                $ret = true;
                break;
            }
        }

        if (null === $ret) {
            // if we had no result before, assume false
            $ret = false;
        }

        response:
        return new JsonResponse(['result' => $ret]);
    }

    /**
     * Downloads an attachment
     *
     * @Security("is_granted('PRIV_MAIL_SEND_AS_GRP')")
     * @Route("/groupmail/download/{messageId}/{attachmentId}", name="group_mail_download", methods={"GET"})
     */
    public function downloadAction(int $messageId, int $attachmentId, UserStorageInterface $userStorage, EntityManagerInterface $em): Response
    {
        $groupRepo = $em->getRepository(GroupMail::class);
        $mail = $groupRepo->find($messageId);

        if (null === $mail) {
            throw $this->createNotFoundException('No mail found.');
        }

        if (!$userStorage->getUser()->hasGroup($mail->getSender())) {
            throw $this->createAccessDeniedException('You are not allowed to view content of this message.');
        }

        $fileRepo = $em->getRepository(GroupMailFile::class);
        $file = $fileRepo->findOneBy(['id' => $attachmentId]);

        if (null === $file) {
            throw $this->createNotFoundException('No file found.');
        }

        if ($file->getMail() !== $mail) {
            throw $this->createAccessDeniedException('Supplied message does not fit to attachment.');
        }

        $quoted = sprintf('"%s"', addcslashes($file->getName(), '"\\'));

        $fileName = sprintf('/var/lib/stsbl/send-mail-as-group/mail-files/%s-%s', $file->getId(), $file->getName());
        $fileObject = new \SplFileObject($fileName, "r");
        $fileContents = $fileObject->fread($fileObject->getSize());

        $response = new Response($fileContents);
        $response->headers->set('Content-Type', $file->getMime());
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $quoted);

        return $response;
    }

    /**
     * Get a form to compose a new e-mail
     */
    private function getForm(): FormInterface
    {
        $builder = $this->get('form.factory')->createNamedBuilder('compose_group_mail');

        $er = $this->container->get(EntityManagerInterface::class)->getRepository(Group::class);

        $groups = $er->createFindByFlagQueryBuilder(Privilege::FLAG_USEABLE_AS_SENDER)
            ->orderBy('LOWER(g.name)', 'ASC')
            ->getQuery()
            ->getResult();

        $choices = [];

        foreach ($groups as $group) {
            if ($group->hasUser($this->getUser())) {
                $choices[] = $group;
            }
        }

        $builder
            ->add('subject', TextType::class, [
                'label' => _('Subject'),
                'required' => true,
                'constraints' => [new NotBlank(['message' => _('Subject should not be empty.')])],
            ])
            ->add('group', EntityType::class, [
                'label' => _('Sender'),
                'class' => Group::class,
                'select2-icon' => 'legacy-act-group',
                'multiple' => false,
                'required' => false,
                'constraints' => [new NotBlank(['message' => _('Sender should not be empty.')])],
                'by_reference' => false,
                'choices' => $choices,
                'attr' => [
                    'placeholder' => _('Select a group...'),
                ],
            ])
            ->add('recipients', AutocompleteTagsType::class, [
                'label' => _('Recipients'),
                'required' => true,
                'constraints' => [new NotBlank(['message' => _('Recipients should not be empty.')])],
                'autocomplete_types' => [AutocompleteType::MAIL],
            ])
            ->add('body', TextareaType::class, [
                'label' => _('Message'),
                'required' => true,
                'constraints' => [new NotBlank(['message' => _('Message should not be empty.')])],
                'attr' => [
                    'rows' => 20,
                ],
            ])
            ->add('attachments', UniversalFileType::class, [
                'required' => false,
                'label' => _('Attachments'),
            ])
            ->add('submit', SubmitType::class, [
                'label' => _('Send'),
                'buttonClass' => 'btn-success',
                'icon' => 'send'
            ])
        ;

        return $builder->getForm();
    }

    /**
     * Prepares sending of an e-mail with the given data of the supplied form.
     * Returns the messages from mail_send_as_group as array.
     */
    private function sendMail(array $data, string $ip, string $fwdIp = null): array
    {
        try {
            $randomNumber = random_int(1000, mt_getrandmax());
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not generate random number.', 0, $e);
        }

        $tmpDir = '/tmp/mail-send-as-group/';
        $dir = $tmpDir . $randomNumber . '/';

        if (!$this->filesystem->exists($tmpDir)) {
            $this->filesystem->mkdir($tmpDir);
        }

        if (!is_writable($tmpDir)) {
            throw new \RuntimeException(sprintf('%s must be writeable, it is not.', $tmpDir));
        }

        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir);

            $msgFile = $dir . 'content.txt';
            $this->filesystem->dumpFile($msgFile, $data['body']);

            /** @var File[] $uploadedFiles */
            $uploadedFiles = $data['attachments'];

            if (count($uploadedFiles) > 0) {
                $attachments = [];

                foreach ($uploadedFiles as $attachment) {
                    $newName = $attachment->getName();
                    $destination = $dir . $newName;

                    $this->filesystem->dumpFile($destination, $attachment->readStream());

                    $attachments[] = $destination;
                }
            } else {
                $attachments = null;
            }

            $group = $data['group'];
            /** @var AutocompleteTagsData[] $recipientData */
            $recipientData = $data['recipients'];
            $recipients = [];

            foreach ($recipientData as $recipient) {
                $recipientValue = $recipient->getValue();

                // Just WHY?
                $source = $recipient->getSource();
                if ($source !== null) {
                    $recipientValue = preg_replace(sprintf('/^%s:/', preg_quote($source, '/')), '', $recipientValue);
                }

                $recipients[] = $recipientValue;
            }

            $msgTitle = $data['subject'];

            $sendMailService = $this->container->get(SendMailAsGroup::class);

            $sendMailService->send($ip, $fwdIp, $group, $recipients, $msgTitle, $msgFile, $attachments);
            $successMessages = $sendMailService->getOutput();
            $errorMessages = $sendMailService->getError();
            $exitCode = $sendMailService->getExitCode();

            // cleanup
            $this->filesystem->remove($dir);

            return ['success' => $successMessages, 'errors' => $errorMessages, 'exitcode' => $exitCode];
        }

        throw new \RuntimeException('Unexpected situation: Random message directory already exists.');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        $deps = parent::getSubscribedServices();

        $deps[] = EntityManagerInterface::class;
        $deps[] = SendMailAsGroup::class;

        return $deps;
    }
}
