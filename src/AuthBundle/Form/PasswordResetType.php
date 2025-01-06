<?php

declare(strict_types=1);

namespace App\AuthBundle\Form;

use App\AuthBundle\Form\RecaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordResetType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('password', PasswordType::class, [
				'required' => false,
				'label' => 'Password',
				'label_attr' => [
					'class' => 'peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6'
				],
				'mapped' => false,
				'attr' => [
					'class' => 'block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer',
					'placeholder' => ' ',
					'minlength' => 1,
					'maxlength' => 30,
				],
				'constraints' => [
					new Assert\NotBlank(['message' => 'Password is required.']),
					new Assert\Length([
						'min' => 8,
						'max' => 30,
						'minMessage' => 'Password must be at least {{ limit }} characters long',
						'maxMessage' => 'Password cannot be longer than {{ limit }} characters',
					]),
					new Assert\Regex([
						'pattern' => '/[A-Z]/',
						'message' => 'Password must contain at least one uppercase letter',
					]),
					new Assert\Regex([
						'pattern' => '/[a-z]/',
						'message' => 'Password must contain at least one lowercase letter',
					]),
					new Assert\Regex([
						'pattern' => '/[0-9]/',
						'message' => 'Password must contain at least one number',
					]),
					new Assert\Regex([
						'pattern' => '/[\W_]/',
						'message' => 'Password must contain at least one symbol',
					]),
				],
				'error_bubbling' => true
			])
			->add('confirm_password', PasswordType::class, [
				'required' => false,
				'label' => 'Confirm password',
				'label_attr' => [
					'class' => 'peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6'
				],
				'mapped' => false,
				'attr' => [
					'class' => 'block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer',
					'placeholder' => ' ',
					'minlength' => 1,
					'maxlength' => 30,
				],
				'constraints' => [
					new Assert\NotBlank(['message' => 'Password is required.']),
					new Assert\Length([
						'min' => 8,
						'max' => 30,
						'minMessage' => 'Password must be at least {{ limit }} characters long',
						'maxMessage' => 'Password cannot be longer than {{ limit }} characters',
					]),
					new Assert\Regex([
						'pattern' => '/[A-Z]/',
						'message' => 'Password must contain at least one uppercase letter',
					]),
					new Assert\Regex([
						'pattern' => '/[a-z]/',
						'message' => 'Password must contain at least one lowercase letter',
					]),
					new Assert\Regex([
						'pattern' => '/[0-9]/',
						'message' => 'Password must contain at least one number',
					]),
					new Assert\Regex([
						'pattern' => '/[\W_]/',
						'message' => 'Password must contain at least one symbol',
					]),
				],
				'error_bubbling' => true
			]);
	}
}