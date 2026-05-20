<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Security\AuthenticationException;

final class SignPresenter extends Presenter
{
	public function startup(): void
	{
		parent::startup();

		if ($this->user->isLoggedIn() && $this->action !== 'logout') {
			$this->redirect('Dashboard:');
		}
	}

	public function actionLogin(): void
	{
	}

	public function actionLogout(): void
	{
		$this->user->logout(true);
		$this->flashMessage('You have been logged out.', 'info');
		$this->redirect('login');
	}

	protected function createComponentLoginForm(): Form
	{
		$form = new Form;

		$form->addEmail('email', 'Email')
			->setRequired('Please enter your email.')
			->setHtmlAttribute('placeholder', 'your@email.com')
			->setHtmlAttribute('autofocus');

		$form->addPassword('password', 'Password')
			->setRequired('Please enter your password.')
			->setHtmlAttribute('placeholder', '••••••••');

		$form->addCheckbox('remember', 'Remember me');

		$form->addSubmit('send', 'Sign In');

		$form->onSuccess[] = $this->loginFormSucceeded(...);

		return $form;
	}

	private function loginFormSucceeded(Form $form, \stdClass $values): void
	{
		try {
			$this->user->setExpiration($values->remember ? '14 days' : '20 minutes');
			$this->user->login($values->email, $values->password);
			$this->redirect('Dashboard:');
		} catch (AuthenticationException $e) {
			$form->addError('Invalid email or password.');
		}
	}
}
