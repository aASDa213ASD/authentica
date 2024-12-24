<?php

declare(strict_types=1);

namespace App\UserBundle\Controller;

use App\UserBundle\Entity\User;
use App\UserBundle\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/system/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
	private EntityManagerInterface $entity_manager;
	private UserPasswordHasherInterface $user_password_encoder;

	public function __construct(
		EntityManagerInterface $entity_manager,
		UserPasswordHasherInterface $user_password_encoder
	) {
		$this->entity_manager = $entity_manager;
		$this->user_password_encoder = $user_password_encoder;
	}

	#[Route('/list', name: 'system_user_list')]
	public function list(Request $request): Response
	{
		$user_list = $this->entity_manager->getRepository(User::class)->findAll();

		return $this->render(
			'@User/list_user.html.twig', [
				'user_list' => $user_list,
			]
		);
	}

	#[Route('/edit/{user_id}', name: 'user_edit', requirements: ['user_id' => '\d+'])]
	#[IsGranted('ROLE_DEVELOPER')]
	public function edit(Request $request, ?int $user_id = null): Response
	{
		if (!$user_id)
		{
			$user  = new User();
			$title = 'Реєстрація користувача';
			$icon  = 'nf-fa-circle_user';
		} else
		{
			$user = $this->entity_manager->getRepository(User::class)->find($user_id);
			$title = "Редагування користувача {$user->getEmail()}";
			$icon  = 'nf-md-account_edit';
		}

		$form = $this->createForm(UserType::class, $user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$password = $form['password']->getData();
			if ($password)
			{
				$encoded = $this->user_password_encoder->hashPassword($user, trim($password));
				$user->setPassword($encoded);
			} else if (!$user->isVerified())
			{
				$this->addFlash('message', 'Пароль не встановлено');
				return $this->redirect($this->generateUrl('user_edit'));
			}

			$user->setIsVerified(true);

			$this->entity_manager->persist($user);
			$this->entity_manager->flush();
			return $this->redirectToRoute('system_user_list');
		}

		return $this->render(
			'@App/edit_form.html.twig', [
				'form'  => $form->createView(),
				'icon'  => $icon,
				'title' => $title,
				'user'  => $user,
			]
		);
	}

	#[Route('/delete/{user_id}', name: 'user_delete', requirements: ['user_id' => '\d+'])]
	#[IsGranted('ROLE_DEVELOPER')]
	public function delete(int $user_id): Response
	{
		$user = $this->entity_manager->getRepository(User::class)->find($user_id);

		if (in_array('ROLE_DEVELOPER', $user->getRoles()))
		{
			$this->addFlash('message', 'Неможливо видалити розробника');
			return $this->redirect($this->generateUrl('system_user_list'));
		}

		$this->entity_manager->remove($user);
		$this->entity_manager->flush();
		return $this->redirectToRoute('system_user_list');
	}

	#[Route('/settings/appearance', name: 'user_settings_appearance')]
	public function settings_appearance(Request $request): Response
	{
		return $this->render('@User/settings_appearance.html.twig');
	}
}