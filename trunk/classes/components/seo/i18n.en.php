<?php
	/** Языковые константы для английской версии */
	$i18n = [
		'header-seo-seo' => 'Position analysis',
		'header-seo-megaindex' => 'MegaIndex settings',
		'perms-seo-seo' => 'SEO functions',
		'perms-seo-guest' => 'Guest permissions',
		'header-seo-config' => 'SEO settings',
		'header-seo-links' => 'Links analysis',
		'header-seo-getBrokenLinks' => 'Pages with broken links',
		'header-seo-emptyMetaTags' => 'Pages with empty meta tags',
		'header-seo-getSiteInfo' => 'Site information from Yandex.WebMaster',
		'label-seo-domain' => 'Domains',
		'option-seo-title' => 'TITLE prefix',
		'option-seo-default-title' => 'TITLE (default)',
		'option-seo-keywords' => 'Keywords (default)',
		'option-seo-description' => 'Description (default)',
		'header-seo-domains' => 'SEO domains settings',
		'label-site-address' => 'Site address',
		'label-site-analysis' => 'Site analysis',
		'label-button' => 'Get results',
		'label-repeat' => 'Repeat',
		'label-results' => 'Results',
		'label-query' => 'Query',
		'label-yandex' => 'Yandex',
		'label-google' => 'Google',
		'label-count' => 'Queries pro month',
		'label-wordstat' => 'Wordstat',
		'label-price' => 'Price',
		'label-link-from' => 'Source',
		'label-link-to' => 'Target',
		'label-tic-from' => 'Source Thematic Citation Index',
		'label-tic-to' => 'Target Thematic Citation Index of donor',
		'label-link-anchor' => 'Link anchor',
		'label-seo-noindex' => 'No information about %s is available in MegaIndex database. Please, register and add your website for index.',
		'option-megaindex-login' => 'MegaIndex login',
		'option-megaindex-password' => 'MegaIndex password',
		'error-invalid_answer' => 'No valid answer from Megaindex. Try again later.',
		'error-authorization-failed' => 'Invalid login or password',
		'error' => 'Error: ',
		'error-data' => 'Error: Invalid data',
		'header-seo-webmaster' => 'Yandex.Webmaster',
		'header-seo-yandex' => 'Yandex.Webmaster settings',
		'footer-webmaster-text' => 'Based on ',
		'footer-webmaster-link' => 'Yandex.Webmaster',
		'option-token' => 'Your current token: ',
		'option-code' => 'Enter validation code',
		'link-code' => 'Get code',
		'label-yes' => 'Yes',
		'label-no' => 'No',
		'error-need-megaindex-registration' => 'To use the module requires registration on the website <a href="http://www.megaindex.ru" target="_blank" title=""> MegaIndex </a>. Sign up for him, and then enter your username and password in the <a href="/admin/seo/megaindex/" title=""> Settings module </a>.',
		'js-field-search' => 'search',
		'label-link-address' => 'Link address',
		'label-page-address' => 'Page address',
		'js-label-view-button' => 'View sources',
		'label-error-links-not-found' => 'Sources of this link is not found, please contact Care Service for help.',
		'js-label-request-error' => 'An error has occurred performing the request to the server, please try again later.',
		'js-label-place-type-template' => 'In template: ',
		'js-label-place-type-object' => 'In object: ',
		'js-label-header-sources' => 'Broken link was found in:',
		'js-label-title-sources' => 'Bad link sources',
		'js-confirm' => 'Ok',
		'label-button-find-bad-links' => 'Find bad links',
		'label-info-DesignTemplates' => 'Looking for links in the design templates...',
		'label-info-ObjectsFields' => 'Looking for links in the object text properties...',
		'label-info-ObjectsNames' => 'Looking for links in the object names...',
		'label-info-SitesUrls' => 'Looking for links in the site pages...',
		'label-info-linksChecker' => 'Checking links...',
		'js-label-step-linksChecker' => 'check',
		'js-label-step-linksGrabber' => 'index',
		'js-label-bad-links-search-complete' => 'Search complete',
		'js-label-close' => 'Close',
		'js-label-interrupt' => 'Interrupt',
		'js-label-bad-links-search' => 'Bad links search',
		'js-label-bad-links-search-start-message' => 'Bad links search starts',
		'js-error-label-unknown-search-step-name' => 'Unknown search step',
		'label-error-seo-admin-not-implemented' => 'We could not use the administrative functionality of the "SEO" module',
		'label-error-yandex-create-verify-file' => 'Could not create verification file',
		'label-error-yandex-wrong-code' => 'Incorrect validation code',
		'label-error-yandex-web-master-invalid-token' => 'Service "Yandex.Webmaster" declined your token. Please check the validity of the <a href="%s/admin/seo/yandex/">token</a>.',
		'label-yandex-site-name' => 'Name',
		'label-yandex-site-address' => 'Address',
		'label-yandex-site-index-state' => 'Indexation',
		'label-yandex-site-verify-state' => 'Verification',
		'label-yandex-site-verify-tic' => 'Thematic index of citation',
		'label-yandex-site-map-added' => 'Site map added',
		'label-yandex-site-downloaded-count' => 'Downloaded pages count',
		'label-yandex-site-excluded-count' => 'Excluded pages count',
		'label-yandex-site-searchable-count' => 'Searchable pages count',
		'label-yandex-site-problems-count' => 'Errors count',
		'js-label-yandex-button-view' => 'View details',
		'js-label-yandex-button-add' => 'Add site to Yandex.WebMaster',
		'js-label-yandex-button-verify' => 'Verify rights',
		'js-label-yandex-button-add_site_map' => 'Add site map to Yandex.WebMaster',
		'js-label-yandex-button-delete' => 'Delete site from Yandex.WebMaster',
		'js-label-yandex-button-refresh' => 'Refresh data',
		'label-yandex-site-status-NOT_ADDED' => 'No added to Yandex.WebMaster',
		'label-yandex-site-status-UNDEFINED' => 'Status not defined',
		'label-yandex-site-status-NOT_INDEXED' => 'Not indexed',
		'label-yandex-site-status-NOT_LOADED' => 'Not loaded',
		'label-yandex-site-status-OK' => 'Indexed and loaded',
		'label-yandex-site-option-null-value' => 'Unknown',
		'label-yandex-verify-status-NONE' => 'No confirmation was sent',
		'label-yandex-verify-status-VERIFIED' => 'Rights confirmed',
		'label-yandex-verify-status-IN_PROGRESS' => 'Verification of rights',
		'label-yandex-verify-status-VERIFICATION_FAILED' => 'Rights not confirmed',
		'label-yandex-verify-status-INTERNAL_ERROR' => 'An error has occurred',
		'label-yandex-external-links' => 'External links',
		'label-yandex-top-popular-queries' => 'Top search queries per week',
		'label-yandex-indexation-history' => 'History of indexing for 2 month',
		'label-yandex-top-popular-queries-shows' => 'Top 5 queries by shows',
		'label-yandex-top-popular-queries-clicks' => 'Top 5 queries by clicks',
		'label-yandex-searchable-pages-history' => 'Change the number of pages in the search',
		'label-yandex-all' => 'All',
		'label-yandex-downloaded-pages-history' => 'Changing the number of pages loaded',
		'label-yandex-downloaded-with-code-2xx' => 'Code 2XX',
		'label-yandex-downloaded-with-code-3xx' => 'Code 3XX',
		'label-yandex-downloaded-with-code-4xx' => 'Code 4XX',
		'label-yandex-downloaded-with-code-5xx' => 'Code 5XX',
		'label-yandex-excluded-pages-history' => 'Change the number of excluded pages',
		'label-yandex-excluded-by-user' => '4xx-code, disallow by robots.txt',
		'label-yandex-excluded-by-site-error' => 'Errors on the site',
		'label-yandex-excluded-by-yandex' => 'Not supported by Yandex',
		'label-yandex-not-downloaded-pages-history' => 'Changing the number of unloaded pages',
		'label-yandex-tic-history' => 'Changing the site\'s Thematic Citation Index',
		'label-yandex-external-links-count-history' => 'Change the number of external links to the site',
		'label-yandex-destination-url' => 'Link',
		'label-yandex-source-url' => 'Address of the link page',
		'label-yandex-discovery-date' => 'Date of discovery',
		'label-yandex-source-last-access-date' => 'Date of last access',
		'label-yandex-date' => 'Date',
		'label-yandex-value' => 'Value',
	];