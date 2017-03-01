<?php
/**
 * Created by PhpStorm.
 * User: Alexander Polevoy <xedinaska@gmail.com>
 * Date: 27.06.16
 * Time: 11:32
 */

namespace Domain\SiteBundle\Form\Handler;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Oxa\ManagerArchitectureBundle\Form\Handler\BaseFormHandler;
use Oxa\ManagerArchitectureBundle\Model\Interfaces\FormHandlerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ResetPasswordFormHandler
 * @package Domain\SiteBundle\Form\Handler
 */
class ResetPasswordFormHandler extends BaseFormHandler implements FormHandlerInterface
{
    protected $translationDomain = 'DomainSiteBundle';

    /** @var FormInterface  */
    protected $form;

    /** @var Request  */
    protected $request;

    /** @var UserManagerInterface */
    protected $userManager;

    /** @var Translator */
    protected $translator;

    /**
     * ResetPasswordFormHandler constructor.
     * @param FormInterface $form
     * @param Request $request
     * @param UserManagerInterface $userManager
     * @param Translator $translator
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        UserManagerInterface $userManager,
        Translator $translator
    ) {
        $this->form           = $form;
        $this->request        = $request;
        $this->userManager    = $userManager;
        $this->translator     = $translator;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function process()
    {
        $token = $this->request->request->get('token', null);

        if ($token === null) {
            throw new \Exception(
                $this->translator->trans('user.reset_password.token.empty')
            );
        }

        $usersManager = $this->getUsersManager();

        $user = $usersManager->findUserByConfirmationToken($token);

        if ($user === null) {
            throw new NotFoundHttpException(
                $this->translator->trans('user.reset_password.token.invalid')
            );
        }

        if ($this->request->getMethod() == 'POST') {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                $password = $this->form->get('plainPassword')->getData();

                $this->onSuccess($user, $password);

                return true;
            }
        }

        return false;
    }

    /**
     * @param UserInterface $user
     * @param string $password
     */
    protected function onSuccess(UserInterface $user, string $password)
    {
        $user->setPlainPassword($password);
        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);
        $user->setEnabled(true);

        $this->getUsersManager()->updateUser($user);
    }

    /**
     * @return UserManagerInterface
     */
    private function getUsersManager() : UserManagerInterface
    {
        return $this->userManager;
    }
}
