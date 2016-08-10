<?php
/**
 * Created by PhpStorm.
 * User: Alexander Polevoy <xedinaska@gmail.com>
 * Date: 23.06.16
 * Time: 10:53
 */

namespace Domain\SiteBundle\Form\Handler;

use Domain\SiteBundle\Mailer\Mailer;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Oxa\ManagerArchitectureBundle\Form\Handler\BaseFormHandler;
use Oxa\ManagerArchitectureBundle\Model\Interfaces\FormHandlerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ResetPasswordFormHandler
 * @package Domain\SiteBundle\Form\Handler
 */
class ResetPasswordRequestFormHandler extends BaseFormHandler implements FormHandlerInterface
{
    const ERROR_USER_NOT_FOUND = 'User %s doesn\'t exists';

    /** @var FormInterface  */
    protected $form;

    /** @var Request  */
    protected $request;

    /** @var UserManagerInterface */
    protected $userManager;

    /** @var  TokenGeneratorInterface */
    protected $tokenGenerator;

    /** @var  Mailer */
    protected $mailer;

    /**
     * ResetPasswordRequestFormHandler constructor.
     * @param FormInterface $form
     * @param Request $request
     * @param UserManagerInterface $userManager
     * @param TokenGeneratorInterface $tokenGenerator
     * @param Mailer $mailer
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        UserManagerInterface $userManager,
        TokenGeneratorInterface $tokenGenerator,
        Mailer $mailer
    ) {
        $this->form           = $form;
        $this->request        = $request;
        $this->userManager    = $userManager;
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer         = $mailer;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function process()
    {
        if ($this->request->getMethod() == 'POST') {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess();
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    protected function onSuccess()
    {
        $email = $this->form->get('email')->getData();

        $usersManager = $this->getUsersManager();

        $user = $usersManager->findUserByUsernameOrEmail($email);

        if ($user === null) {
            throw new \Exception(sprintf(self::ERROR_USER_NOT_FOUND, $email));
        }

        if ($user->getConfirmationToken() === null) {
            $user->setConfirmationToken($this->getTokenGenerator()->generateToken());
        }

        $this->getMailer()->sendResetPasswordEmailMessage($user);

        $user->setPasswordRequestedAt(new \DateTime());

        $usersManager->updateUser($user);
    }

    /**
     * @return Mailer
     */
    private function getMailer() : Mailer
    {
        return $this->mailer;
    }

    /**
     * @return TokenGeneratorInterface
     */
    private function getTokenGenerator() : TokenGeneratorInterface
    {
        return $this->tokenGenerator;
    }

    /**
     * @return UserManagerInterface
     */
    private function getUsersManager() : UserManagerInterface
    {
        return $this->userManager;
    }
}
