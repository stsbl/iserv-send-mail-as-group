<?php
// src/Stsbl/SendMailAsGroupBundle/Crud/GroupMailCrud.php
namespace Stsbl\SendMailAsGroupBundle\Crud;

use Doctrine\ORM\EntityManager;
use IServ\CoreBundle\Entity\Specification\GroupsMembershipSpecification;
use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use IServ\CrudBundle\Table\ListHandler;
use IServ\CrudBundle\Table\Filter;
use Stsbl\SendMailAsGroupBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

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
 * Crud for viewing logs of sended mails
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/liceneses/MIT>*
 */
class GroupMailCrud extends AbstractCrud
{
    /**
     * @var EntityManager 
     */
    private $em;

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
        $this->routesPrefix = 'groupmail';
        $this->templates['crud_index'] = 'StsblSendMailAsGroupBundle:Crud:groupmail_index.html.twig';
        $this->templates['crud_show'] = 'StsblSendMailAsGroupBundle:Crud:groupmail_show.html.twig';
    }
    
    /**
     * Set EntityManager
     * 
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
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
    public function getRoutePattern($action, $id, $entityBased = true)
    {
        // Overwrite broken route generation of Crud (WHY? =()
        if ('index' === $action) {
            return sprintf('%s', $this->routesPrefix);
        } else if ('add' === $action) {
            return sprintf('%s/%s', $this->routesPrefix, $action);
        } else if ('batch' === $action) {
            return sprintf('%s/%s', $this->routesPrefix, $action);
        } else if ('batch/confirm' === $action) {
            return sprintf('%s%s/%s', $this->routesPrefix, 'batch', 'confirm');
        } else if ('show' === $action) {
            return sprintf('%s/%s/%s', $this->routesPrefix, $action, '{id}');
        } else if ('edit' === $action) {
            return sprintf('%s/%s/%s', $this->routesPrefix, $action, '{id}');
        } else if ('delete' === $action) {
           return sprintf('%s/%s/%s', $this->routesPrefix, $action, '{id}');
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureListFilter(ListHandler $listHandler)
    {
        /* @var $qb \Doctrine\ORM\QueryBuilder */
        $qb = $this->getObjectManager()->createQueryBuilder($this->class);
        
        $qb
            ->select('p')
            ->from('StsblSendMailAsGroupBundle:GroupMail', 'p')
        ;
        
        /* @var $groupRepository \IServ\CoreBundle\Entity\GroupRepository */
        $groupRepository = $this->em->getRepository('IServCoreBundle:Group');
        
        /* @var $groups \IServ\CoreBundle\Entity\Group[] */
        $groups = $groupRepository->findByFlag(Privilege::FLAG_USEABLE_AS_SENDER);
        $groupsWithUser = [];
        $user = $this->getUser();
        
        foreach ($groups as $g) {
            if ($g->hasUser($user)) {
                $groupsWithUser[] = $g;
            }
        }
        
        $i = 1;
        foreach ($groupsWithUser as $g) {
            $string = sprintf('p.sender = \'%s\'', $g->getAccount());
            if ($i === 1) {
                $qb->where($string);
            } else {
                $qb->orWhere($string);
            }

            $i++;
        }
        
        $allFilter = new Filter\ListExpressionFilter(_('All groups'), $qb->expr()->exists($qb));
        $allFilter->setName('all_groups');
        
        $filters = [];
        foreach ($groupsWithUser as $g) {
            $filter = new Filter\ListExpressionFilter((string)$g, 'parent.sender = :group');
            $filter
                ->setName('group_'.$g->getAccount())
                ->setParameters(['group' => $g])
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
