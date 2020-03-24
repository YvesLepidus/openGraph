<?php

/**
 * @file plugins/generic/openGraph/OpenGraphPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OpenGraphPlugin
 * @ingroup plugins_generic_openGraph
 *
 * @brief Inject Open Graph meta tags into submission views to facilitate indexing.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class OpenGraphPlugin extends GenericPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled($mainContextId)) {
				HookRegistry::register('ArticleHandler::view', array(&$this, 'submissionView'));
				HookRegistry::register('PreprintHandler::view', array(&$this, 'submissionView'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new context
	 * creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Inject Open Graph metadata into submission landing page view
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function submissionView($hookName, $args) {
		$application = Application::get();
		$applicationName = $application->getName();
		$request = $args[0];
		$context = $request->getContext();
		if ($applicationName == "ojs2"){
			$issue = $args[1];
			$submission = $args[2];
			$submissionPath = 'article';
		}
		if ($applicationName == "ops"){
			$submission = $args[1];
			$submissionPath = 'preprint';
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->addHeader('openGraphSiteName', '<meta name="og:site_name" content="' . htmlspecialchars($context->getName($context->getPrimaryLocale())) . '"/>');
		$templateMgr->addHeader('openGraphObjectType', '<meta name="og:type" content="article"/>');
		$templateMgr->addHeader('openGraphTitle', '<meta name="og:title" content="' . htmlspecialchars($submission->getFullTitle($submission->getLocale())) . '"/>');
		if ($abstract = PKPString::html2text($submission->getAbstract($submission->getLocale()))) $templateMgr->addHeader('openGraphDescription', '<meta name="og:description" content="' . htmlspecialchars($abstract) . '"/>');
		$templateMgr->addHeader('openGraphUrl', '<meta name="og:url" content="' . $request->url(null, $submissionPath, 'view', array($submission->getBestId())) . '"/>');
		if ($locale = $submission->getLocale()) $templateMgr->addHeader('openGraphLocale', '<meta name="og:locale" content="' . htmlspecialchars($locale) . '"/>');

		$openGraphImage = "";
		if ($contextPageHeaderLogo = $context->getLocalizedPageHeaderLogo()){
			$openGraphImage = $templateMgr->getTemplateVars('publicFilesDir') . "/" . $contextPageHeaderLogo['uploadName'];
		}
		if ($issue && $issueCoverImage = $issue->getLocalizedCoverImageUrl()){
			$openGraphImage = $issueCoverImage;
		}
		if ($submissionCoverImage = $submission->getLocalizedCoverImageUrl()){
			$openGraphImage = $submissionCoverImage;
		}
		$templateMgr->addHeader('openGraphImage', '<meta name="og:image" content="' . $openGraphImage . '"/>');

		if ($datePublished = $submission->getDatePublished())$templateMgr->addHeader('openGraphDate', '<meta name="article:published_time" content="' . strftime('%Y-%m-%d', strtotime($datePublished)) . '"/>');

		$i=0;
		$dao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$keywords = $dao->getKeywords($submission->getCurrentPublication()->getId(), array(AppLocale::getLocale()));
		foreach ($keywords as $locale => $localeKeywords) {
			foreach ($localeKeywords as $keyword) {
				$templateMgr->addHeader('openGraphArticleTag' . $i++, '<meta name="article:tag" content="' . htmlspecialchars($keyword) . '"/>');
			}
		}

		return false;
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.openGraph.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.openGraph.description');
	}
}


