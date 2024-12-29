<?php

declare(strict_types=1);

namespace App\AuthBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints as Assert;

class RecaptchaType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('recaptcha', HiddenType::class, [
				'required' => true,
				'attr' => ['class' => 'g-recaptcha',],
				'mapped' => false,
				'label' => false,
			]);
	}
}
