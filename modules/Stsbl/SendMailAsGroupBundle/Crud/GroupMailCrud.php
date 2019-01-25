<?php

namespace Stsbl\SendMailAsGroupBundle\Crud;

use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\Specification\GroupsMembershipSpecification;
use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Doctrine\ORM\ORMObjectManager;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use IServ\CrudBundle\Table\Filter;
use IServ\CrudBundle\Table\ListHandler;
use Stsbl\SendMailAsGroupBundle\Entity\GroupMail;
use Stsbl\SendMailAsGroupBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

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
 * Crud for viewing logs of sended mails
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/liceneses/MIT>*
 */
class GroupMailCrud extends AbstractCrud
{
    public function __construct()
    {
        parent::__construct(GroupMail::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->title = _('Group e-mail');
        $this->itemTitle = _('E-mail');
        $this->id = 'group_mail';
        // set to empty to remove crud prefix
        $this->routesNamePrefix = '';
        $this->templates['crud_index'] = 'StsblSendMailAsGroupBundle:Crud:groupmail_index.html.twig';
        $this->templates['crud_show'] = 'StsblSendMailAsGroupBundle:Crud:groupmail_show.html.twig';
    }

    /* Disallow adding and editing of items */
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToAdd(UserInterface $user = null)
    {
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToEdit(CrudInterface $object = null, UserInterface $user = null)
    {
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToDelete(CrudInterface $object = null, UserInterface $user = null)
    {
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper->addIdentifier('messageTitle', null, ['label' => _('Title')]);
        $listMapper->add('recipients', null, ['label' => _('Recipients')]);
        $listMapper->add('sender', null, ['label' => _('Sending group'), 'responsive' => 'min-tablet']);
        $listMapper->add('date', null, ['label' => _('Date')]);
        $listMapper->add('messageBody', null, ['label' => _('Message text'), 'template' => 'StsblSendMailAsGroupBundle:List:field_messagetext.html.twig']);
        $listMapper->add('files', null, ['label' => _('Attached files'), 'responsive' => 'desktop', 'template' => 'StsblSendMailAsGroupBundle:List:field_attachment.html.twig']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper->add('messageTitle', null, ['label' => _('Title')]);
        $showMapper->add('recipients', null, ['label' => _('Recipients')]);
        $showMapper->add('sender', null, ['label' => _('Sending group')]);
        $showMapper->add('date', null, ['label' => _('Date')]);
        $showMapper->add('messageBody', null, ['label' => _('Message text'), 'template' => 'StsblSendMailAsGroupBundle:Show:field_messagetext.html.twig']);
        $showMapper->add('files', null, ['label' => _('Attached files'), 'template' => 'StsblSendMailAsGroupBundle:Show:field_attachment.html.twig']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRouteIdentifier()
    {
        return 'groupmail';
    }

    /**
     * {@inheritdoc}
     */
    public function configureListFilter(ListHandler $listHandler)
    {
        /** @var ORMObjectManager $om */
        $om = $this->getObjectManager();

        $qb = $om->createQueryBuilder($this->class);
        
        $qb
            ->select('p')
            ->from('StsblSendMailAsGroupBundle:GroupMail', 'p')
        ;

        /* @var $groupRepository \IServ\CoreBundle\Entity\GroupRepository */
        $groupRepository = $om->getRepository(Group::class);
        
        /* @var $groups \IServ\CoreBundle\Entity\Group[] */
        $groups = $groupRepository->findByFlag(Privilege::FLAG_USEABLE_AS_SENDER);
        $groupsWithUser = [];
        $user = $this->getUser();
        
        foreach ($groups as $group) {
            if ($group->hasUser($user)) {
                $groupsWithUser[] = $group;
            }
        }

        foreach ($groupsWithUser as $group) {
            $qb->orWhere($qb->expr()->eq('p.sender', $qb->expr()->literal($group->getAccount())));
        }
        
        $allFilter = new Filter\ListExpressionFilter(_('All groups'), $qb->expr()->exists($qb));
        $allFilter->setName('all_groups');
        
        $filters = [];
        foreach ($groupsWithUser as $group) {
            $filter = new Filter\ListExpressionFilter((string)$group, 'parent.sender = :group');
            $filter
                ->setName('group_'.$group->getAccount())
                ->setParameters(['group' => $group])
            ;
            
            $filters[] = $filter;
        }
        
        $listHandler
            ->addListFilter($allFilter)
            ->addListFilter(new Filter\ListSearchFilter('search', ['messageTitle', 'messageBody']));
        
        foreach ($filters as $filter) {
            $listHandler->addListFilter($filter);
        }
        
        $listHandler->setDefaultFilter('all_groups');
    }
    
    /**
     * {@inheritdoc}
     */
    protected function buildRoutes()
    {
        parent::buildRoutes();
        
        $this->routes[self::ACTION_INDEX]['_controller'] = 'StsblSendMailAsGroupBundle:Crud:index';
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAuthorized()
    {
        return $this->isGranted(Privilege::SEND_AS_GROUP);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFilterSpecification()
    {
        return new GroupsMembershipSpecification('sender', $this->getUser());
    }
}
