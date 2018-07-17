<?php

	use UmiCms\Service;

	/** Класс обработчиков событий */
	class BlogsHandlers {

		/** @var blogs20 $module */
		public $module;

		/**
		 * Обработчик события создания комментария с клиентской части.
		 * Отправляет уведомление о комментарии автора поста блога.
		 * @param iUmiEventPoint $eventPoint событие создания комментария
		 * @return bool
		 */
		public function onCommentAdd(iUmiEventPoint $eventPoint) {
			$umiRegistry = Service::Registry();
			$umiHierarchy = umiHierarchy::getInstance();
			$umiObjects = umiObjectsCollection::getInstance();
			$umiHierarchyTypes = umiHierarchyTypesCollection::getInstance();

			if (!$umiRegistry->get('//modules/blogs20/notifications/on_comment_add')) {
				return false;
			}

			$templateParam = $eventPoint->getParam('template');
			$template = $templateParam ?: 'default';
			$commentId = $eventPoint->getParam('id');
			$parentId = $umiHierarchy->getElement($commentId, true)->getParentId();
			$element = $umiHierarchy->getElement($parentId);
			$postHierarchyTypeId = $umiHierarchyTypes->getTypeByName('blogs20', 'post')->getId();
			$post = $element;

			if (!$post instanceof iUmiHierarchyElement) {
				return false;
			}

			while ($post->getTypeId() != $postHierarchyTypeId) {
				$post = $umiHierarchy->getElement($post->getParentId(), true);
			}

			if ($element->getTypeId() == $postHierarchyTypeId) {
				$parentOwner = $umiObjects->getObject($element->getObject()->getOwnerId());

				if (!$parentOwner instanceof iUmiObject) {
					return false;
				}

				$email = $parentOwner->getValue('e-mail');
				$nick = $parentOwner->getValue('login');
				$firstName = $parentOwner->getValue('fname');
				$lastName = $parentOwner->getValue('lname');
				$fatherName = $parentOwner->getValue('father_name');
				$name = mb_strlen($firstName) ? ($firstName . ' ' . $fatherName . ' ' . $lastName) : $nick;

				$subjectTemplateLabel = 'comment_for_post_subj';
				$contentTemplateLabel = 'comment_for_post_body';
				$notificationName = 'notification-blogs-post-comment';
				$subjectTemplateName = 'blogs-post-comment-subject';
				$contentTemplateName = 'blogs-post-comment-content';
			} else {
				$parentOwner = $umiObjects->getObject($element->getValue('author_id'));

				if ($parentOwner->getValue('is_registrated')) {
					$user = $umiObjects->getObject($parentOwner->getValue('user_id'));
					$email = $user->getValue('e-mail');
					$nick = $user->getValue('login');
					$firstName = $user->getValue('fname');
					$lastName = $user->getValue('lname');
					$fatherName = $user->getValue('father_name');
					$name = mb_strlen($firstName) ? ($firstName . ' ' . $fatherName . ' ' . $lastName) : $nick;
				} else {
					$email = $parentOwner->getValue('email');
					$name = $parentOwner->getValue('nickname');
				}

				$subjectTemplateLabel = 'comment_for_comment_subj';
				$contentTemplateLabel = 'comment_for_comment_body';
				$notificationName = 'notification-blogs-comment-comment';
				$subjectTemplateName = 'blogs-comment-comment-subject';
				$contentTemplateName = 'blogs-comment-comment-content';
			}

			$domain = Service::DomainDetector()->detectUrl();
			$link = $domain . $umiHierarchy->getPathById($post->getId()) . '#comment_' . $commentId;

			$variables = [
				'link' => $link,
				'name' => $name,
			];

			$subject = null;
			$content = null;

			if ($this->module->isUsingUmiNotifications()) {
				$mailNotifications = Service::MailNotifications();
				$notification = $mailNotifications->getCurrentByName($notificationName);

				if ($notification instanceof MailNotification) {
					$subjectTemplate = $notification->getTemplateByName($subjectTemplateName);
					$contentTemplate = $notification->getTemplateByName($contentTemplateName);

					if ($subjectTemplate instanceof MailTemplate) {
						$subject = $subjectTemplate->getProcessedContent($variables);
					}

					if ($contentTemplate instanceof MailTemplate) {
						$content = $contentTemplate->getProcessedContent($variables);
					}
				}
			} else {
				try {
					list($subjectTemplate, $contentTemplate) = blogs20::loadTemplatesForMail(
						'blogs20/mail/' . $template,
						$subjectTemplateLabel,
						$contentTemplateLabel
					);
					$subject = blogs20::parseTemplateForMail($subjectTemplate, $variables);
					$content = blogs20::parseTemplateForMail($contentTemplate, $variables);
				} catch (Exception $e) {
					// nothing
				}
			}

			if ($subject === null || $content === null) {
				return false;
			}

			$fromEmail = $umiRegistry->get('//settings/email_from');
			$fromName = $umiRegistry->get('//settings/fio_from');

			$mail = new umiMail();
			$mail->addRecipient($email, $name);
			$mail->setFrom($fromEmail, $fromName);
			$mail->setSubject($subject);
			$mail->setContent($content);
			$mail->commit();
			$mail->send();
		}

		/**
		 * Обработчик события создания поста с клиентской части.
		 * Запускает проверку поста на предмет наличия спама.
		 * @param iUmiEventPoint $event событие создания поста
		 */
		public function onPostAdded(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$postId = $event->getParam('id');
				antiSpamHelper::checkForSpam($postId);
			}
		}

		/**
		 * Обработчик события создания комментария с клиентской части.
		 * Запускает проверку комментария на предмет наличия спама.
		 * @param iUmiEventPoint $event событие создания комментария
		 */
		public function onCommentAdded(iUmiEventPoint $event) {
			$commentId = $event->getParam('id');
			antiSpamHelper::checkForSpam($commentId);
		}

	}
