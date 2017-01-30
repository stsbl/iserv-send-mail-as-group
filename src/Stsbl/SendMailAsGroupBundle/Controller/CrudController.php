<?php
// src/Stsbl/SendMailAsGroupBundle/Controller/CrudController.php
namespace Stsbl\SendMailAsGroupBundle\Controller;

use Braincrafted\Bundle\BootstrapBundle\Form\Type\BootstrapCollectionType;
use Doctrine\ORM\NoResultException;
use IServ\CrudBundle\Controller\CrudController as BaseCrudController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Stsbl\SendMailAsGroupBundle\Security\Privilege;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
class CrudController extends BaseCrudController
{
    /*
     * @var Filesystem
     */
    private $filesystem;
    
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
     * @Route("groupmail/lookup", name="group_mail_autocomplete", options={"expose" = true})
     * @Security("is_granted('PRIV_MAIL_SEND_AS_GRP')")
     * @Method("GET")
     * @param Request $request
     * @return JsonResponse
     */
    public function lookupAction(Request $request)
    {
        // Get query from request
        $query = $request->query->get('query');
        $explodedQuery = explode(',', $query);
        
        // only search for last element
        $search = trim(array_pop($explodedQuery));
        $excludeLists = true;
        $result =  [];

        if (null !== $search && '' != $query) {
            /* @var $al \IServ\AddressbookBundle\Service\Addressbook */
            $al = $this->get('iserv.addressbook');
            $result = $al->lookup($search, $excludeLists);
            
            $originalQuery = implode(', ', $explodedQuery);
            $i = 0;
            
            while ($i < count($result)) {
                // append result to original query
                if (count($explodedQuery) > 0) {
                    $result[$i]['value'] = $originalQuery.', '.$result[$i]['value'];
                }
                
                $i++;
            }
        }
        // Return a json response
        return new JsonResponse($result);
    }
        
    /**
     * Sends an e-mail in background
     * 
     * @Security("is_granted('PRIV_MAIL_SEND_AS_GRP')")
     * @Route("groupmail/send", name="group_mail_send", options={"expose": true})
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function sendAction(Request $request)
    {
        $form = $this->getForm();
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $ret = $this->sendMail($form, $request);
            
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
        } else {
            /* @var $errors \Symfony\Component\Form\FormErrorIterator */
            $errors = $form->getErrors(true);
        
            if  ($errors->count() < 1) {
                $jsonErrors[] = ['type' => 'error', 'message' => _('Unexcpected error during sending e-mail. Please try again.')];
            } else {
       
                foreach ($errors as $error) {
                    /* @var $error \Symfony\Component\Form\FormError */
                    $message = $error->getMessage();
                
                    if (!empty($message)) {
                    $jsonErrors[] = ['type' => 'error', 'message' => _($message)];
                    }
                }
            }
            
            return new JsonResponse(['result' => 'failed', 'messages' => $jsonErrors]);
        }
    }
    
    /**
     * Downloads an attachment
     * 
     * @Security("is_granted('PRIV_MAIL_SEND_AS_GRP')")
     * @Route("groupmail/download/{messageid}/{attachmentid}", name="group_mail_download")
     * @Method("GET")
     * @param Request $request
     * @param integer $messageId
     * @param integer $attachmentId
     * @return Response
     */
    public function downloadAction(Request $request, $messageid, $attachmentid)
    {
        try {
            $groupRepo = $this->getDoctrine()->getRepository('StsblSendMailAsGroupBundle:GroupMail');
            /* @var $mail \Stsbl\SendMailAsGroupBundle\Entity\GroupMail */
            $mail = $groupRepo->find($messageid);
            
            if (is_null($mail)) {
                throw new NoResultException();
            }
            
            if (!$this->getUser()->hasGroup($mail->getSender())) {
                throw $this->createAccessDeniedException('You are not allowed to view content of this message.');
            }
        
            $fileRepo = $this->getDoctrine()->getRepository('StsblSendMailAsGroupBundle:GroupMailFile');
            /* @var $file \Stsbl\SendMailAsGroupBundle\Entity\GroupMailFile */
            $file = $fileRepo->findOneBy(['id' => $attachmentid]);
            
            if (is_null($file)) {
                throw new NoResultException();
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
            $response->headers->set('Content-Disposition', 'attachment; filename='.$quoted);
        
            return $response;
        } catch (NoResultException $e) {
            throw $this->createNotFoundException(sprintf('No result found: %s', $e->getMessage()));
        }
    }
    
    /**
     * Get a form to compose a new e-mail
     * 
     * @return Form
     */
    private function getForm()
    {
        $builder = $this->get('form.factory')->createNamedBuilder('compose_group_mail');
        
        $er = $this->getDoctrine()->getRepository('IServCoreBundle:Group');
        /* @var $groups \IServ\CoreBundle\Entity\Group[] */
        $groups = $er->createFindByFlagQueryBuilder(Privilege::FLAG_USEABLE_AS_SENDER)->orderBy('LOWER(g.name)', 'ASC')->getQuery()->getResult();
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
                'constraints' => [new NotBlank(['message' => 'Subject should not be empty.'])],
            ])
            ->add('group', EntityType::class, [
                'label' => _('Sender'),
                'class' => 'IServCoreBundle:Group',
                'select2-icon' => 'legacy-act-group',
                'multiple' => false,
                'required' => false,
                'constraints' => [new NotBlank(['message' => 'Sender should not be empty.'])],
                'by_reference' => false,
                'choices' => $choices,
                'attr' => [
                    'placeholder' => _('Select a group...'),
                ],
            ])
            ->add('recipients', TextType::class, [
                'label' => _('Recipients'),
                'required' => true,
                'constraints' => [new NotBlank(['message' => 'Recipients should not be empty.'])],
                'attr' => [
                    'help_text' => _('Separate multiple recipients with a comma.'),
                ],
            ])
            ->add('body', TextareaType::class, [
                'label' => _('Message'),
                'required' => true,
                'constraints' => [new NotBlank(['message' => 'Message should not be empty.'])],
                'attr' => [
                    'rows' => 20,
                ],
            ])
            ->add('attachments', BootstrapCollectionType::class, [
                'required' => false,
                'label' => _('Attachments'),
                'entry_type' => FileType::class,
                'prototype_name' => 'proto-entry',
                // Child options
                'entry_options' => [
                    'required' => false,
                    'attr' => [
                        'widget_col' => 12, // Single child field w/o label col
                    ],
                ],
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
     * 
     * @param Form $form
     * @param Request $request
     * @return array
     */
    private function sendMail(Form $form, Request $request)
    {
        $data = $form->getData();
        $randomNumber = rand(1000, getrandmax());
        $tmpDir = '/tmp/mail-send-as-group/';
        $dir = $tmpDir.$randomNumber.'/';
        
        if (!$this->filesystem->exists($tmpDir)) {
            $this->filesystem->mkdir($tmpDir);
        }
        
        if (!is_writable($tmpDir)) {
            throw new \RuntimeException(sprintf('%s must be writeable, it is not.', $tmpDir));
        }
        
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir);
            
            $msgFile = $dir.'content.txt';
            $this->filesystem->dumpFile($msgFile, $data['body'], 664); 
            
            $uploadedFiles = $data['attachments'];
            
            if (count($uploadedFiles) > 0) {
                $attachments = [];
                
                $i = 0;
                foreach ($uploadedFiles as $attachment) {
                    $newName = $attachment->getClientOriginalName();
                    /* @var $attachment UploadedFile */
                    $attachment->move($dir, $newName);
                    
                    $attachments[] = $dir.$newName;
                    $i++;
                }
            } else {
                $attachments = null;
            }
            
            $group = $data['group'];
            $recipients = explode(',', $data['recipients']);
            
            $i = 0;
            while ($i < count($recipients)) {
                // remove leading and ending spaces
                $recipients[$i] = trim($recipients[$i]);
                $i++;
            }
            
            $msgTitle = $data['subject'];
            
            /* @var $sendMailService \Stsbl\SendMailAsGroupBundle\Service\SendMailAsGroup */
            $sendMailService = $this->get('stsbl.sendmailsasgroup.service.send');
            
            $sendMailService->send($group, $recipients, $msgTitle, $msgFile, $attachments);
            $successMessages = $sendMailService->getOutput();
            $errorMessages = $sendMailService->getError();
            $exitCode = $sendMailService->getExitCode();
            
            // cleanup
            $this->filesystem->remove($dir);
            
            return ['success' => $successMessages, 'errors' => $errorMessages, 'exitcode' => $exitCode];
        } else {
            throw new \RuntimeException('Unexpected situation: Random message directory already exists.');
        }
    }
}
