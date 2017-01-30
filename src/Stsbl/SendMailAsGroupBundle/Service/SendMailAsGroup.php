<?php
// src/Stsbl/SendMailAsGroupBundle/Service/SendMailAsGroup.php
namespace Stsbl\SendMailAsGroupBundle\Service;

use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Service\Shell;

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
 * Description of SendMailAsGroup
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/liceneses/MIT>
 */
class SendMailAsGroup 
{
    const COMMAND = '/usr/lib/iserv/mail_send_as_group';
    
    /**
     * @var Shell
     */
    private $shell;
    
    /**
     * @var SecurityHandler
     */
    private $securityHandler;
    
    /**
     * @var Config     
     */
    private $config;

    /**
     * The constructor
     */
    public function __construct(Shell $shell, SecurityHandler $securityHandler, Config $config) 
    {
        $this->shell = $shell;
        $this->securityHandler = $securityHandler;
        $this->config = $config;
    }
    
    /**
     * Sends an e-mail with the given group as sender
     * 
     * @param Group $group
     * @param array $recipients
     * @param string $msgTitle
     * @param string $contentFile
     * @param array $attachments
     */
    public function send(Group $group, array $recipients, $msgTitle, $contentFile, array $attachments = null)
    {
        $ip = @$_SERVER["REMOTE_ADDR"];
        $fwdIp = preg_replace("/.*,\s*/", "", @$_SERVER["HTTP_X_FORWARDED_FOR"]);
        $act = $this->securityHandler->getUser()->getUsernameForActAdm();
        $groupAct = $group->getAccount();
        $sessionPassword = $this->securityHandler->getSessionPassword();
        
        $recipientAddresses = [];
        $recipientDisplay = [];
        
        // split e-mail address information
        foreach ($recipients as $recipient) {
            $address = imap_rfc822_parse_adrlist($recipient, $this->config->get('Servername'));
            
            if (!is_array($address) || count($address) !== 1) {
                throw new \RuntimeExecption('Invalid result during e-mail address parsing.');
            }
            
            $mergedAddress = sprintf('%s@%s', $address[0]->mailbox, $address[0]->host);
            $recipientAddresses[] = $mergedAddress;
            if (!empty($address[0]->personal)) {
                // try to use extracted name
                $recipientDisplay[] = $address[0]->personal;
            } else {
                // elsewhere use quoted merged mailbox and host as display name
                $recipientDisplay[] = sprintf('"%s"', $mergedAddress);
            }
        }
        
        $recipientArg = implode(',', $recipientAddresses);
        $recipientDisplayArg = implode(',', $recipientDisplay);
        if (count($attachments) > 0) {
            $attachmentsArg = implode(',', $attachments);
        } else {
            // only scalars are allowed
            $attachmentsArg = '';
        }
        
        // set LC_ALL to a translation with utf-8 to prevent destroying umlauts
        $environment = ['LC_ALL' => 'en_US.UTF-8', 'IP' => $ip, 'IPFWD' => $fwdIp, 'SESSPW' => $sessionPassword];
        $this->shell->exec('closefd', ['sudo', self::COMMAND, $act, $groupAct, $recipientArg, $recipientDisplayArg, $msgTitle, $contentFile, $attachmentsArg], null, $environment);
    }

    /**
     * Gets output
     * 
     * @return array
     */
    public function getOutput()
    {
        return $this->shell->getOutput();
    }
    
    /**
     * Gets error output
     * 
     * @return array
     */
    public function getError()
    {
        return $this->shell->getError();
    }
    
    /**
     * Gets exit code of last executed command
     * 
     * @return integer
     */
    public function getExitCode()
    {
        return $this->shell->getExitCode();
    }
}
