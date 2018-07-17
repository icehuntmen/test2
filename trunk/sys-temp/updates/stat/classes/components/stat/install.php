<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [
		'name' => 'stat',
		'config' => '1',
		'default_method' => 'empty',
		'default_method_admin' => 'yandexMetric',
		'collect' => '0',
		'delete_after' => '30',
		'items_per_page' => '100',
	];

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [
		'./classes/components/stat/classes/Yandex/Metric/Client.php',
		'./classes/components/stat/classes/Yandex/Metric/iClient.php',
		'./classes/components/stat/classes/Yandex/ModuleApi/Admin.php',
		'./classes/components/stat/classes/Registry.php',
		'./classes/components/stat/classes/iRegistry.php',
		'./classes/components/stat/admin.php',
		'./classes/components/stat/autoload.php',
		'./classes/components/stat/class.php',
		'./classes/components/stat/customAdmin.php',
		'./classes/components/stat/customMacros.php',
		'./classes/components/stat/handlers.php',
		'./classes/components/stat/i18n.en.php',
		'./classes/components/stat/i18n.php',
		'./classes/components/stat/includes.php',
		'./classes/components/stat/install.php',
		'./classes/components/stat/lang.en.php',
		'./classes/components/stat/lang.php',
		'./classes/components/stat/macros.php',
		'./classes/components/stat/permissions.php',
		'./classes/components/stat/classes/holidayRoutineCounter.php',
		'./classes/components/stat/classes/openstat.php',
		'./classes/components/stat/classes/simpleStat.php',
		'./classes/components/stat/classes/statistic.php',
		'./classes/components/stat/classes/statisticFactory.php',
		'./classes/components/stat/classes/xmlDecorator.php',
		'./classes/components/stat/classes/decorators/allTagsXml.php',
		'./classes/components/stat/classes/decorators/auditoryActivityXml.php',
		'./classes/components/stat/classes/decorators/auditoryLoyalityXml.php',
		'./classes/components/stat/classes/decorators/auditoryVolumeGrowthXml.php',
		'./classes/components/stat/classes/decorators/auditoryVolumeXml.php',
		'./classes/components/stat/classes/decorators/cityStatXml.php',
		'./classes/components/stat/classes/decorators/entryByRefererXml.php',
		'./classes/components/stat/classes/decorators/entryPointsXml.php',
		'./classes/components/stat/classes/decorators/exitPointsXml.php',
		'./classes/components/stat/classes/decorators/fastUserTagsXml.php',
		'./classes/components/stat/classes/decorators/hostsCommonXml.php',
		'./classes/components/stat/classes/decorators/openstatAdsXml.php',
		'./classes/components/stat/classes/decorators/openstatCampaignsXml.php',
		'./classes/components/stat/classes/decorators/openstatServicesXml.php',
		'./classes/components/stat/classes/decorators/openstatSourcesXml.php',
		'./classes/components/stat/classes/decorators/pageNextXml.php',
		'./classes/components/stat/classes/decorators/pagesHitsXml.php',
		'./classes/components/stat/classes/decorators/pathsXml.php',
		'./classes/components/stat/classes/decorators/refererByEntryXml.php',
		'./classes/components/stat/classes/decorators/sectionHitsXml.php',
		'./classes/components/stat/classes/decorators/sourcesDomainsConcreteXml.php',
		'./classes/components/stat/classes/decorators/sourcesDomainsXml.php',
		'./classes/components/stat/classes/decorators/sourcesSEOConcreteXml.php',
		'./classes/components/stat/classes/decorators/sourcesSEOKeywordsConcreteXml.php',
		'./classes/components/stat/classes/decorators/sourcesSEOKeywordsXml.php',
		'./classes/components/stat/classes/decorators/sourcesSEOXml.php',
		'./classes/components/stat/classes/decorators/sourcesTopXml.php',
		'./classes/components/stat/classes/decorators/tagXml.php',
		'./classes/components/stat/classes/decorators/userStatXml.php',
		'./classes/components/stat/classes/decorators/visitCommonHoursXml.php',
		'./classes/components/stat/classes/decorators/visitCommonXml.php',
		'./classes/components/stat/classes/decorators/visitersCommonHoursXml.php',
		'./classes/components/stat/classes/decorators/visitersCommonXml.php',
		'./classes/components/stat/classes/decorators/visitTimeXml.php',
		'./classes/components/stat/classes/reports/allTags.php',
		'./classes/components/stat/classes/reports/auditoryActivity.php',
		'./classes/components/stat/classes/reports/auditoryLoyality.php',
		'./classes/components/stat/classes/reports/auditoryVolumeGrowth.php',
		'./classes/components/stat/classes/reports/auditoryVolume.php',
		'./classes/components/stat/classes/reports/cityStat.php',
		'./classes/components/stat/classes/reports/entryByReferer.php',
		'./classes/components/stat/classes/reports/entryPoints.php',
		'./classes/components/stat/classes/reports/exitPoints.php',
		'./classes/components/stat/classes/reports/fastUserTags.php',
		'./classes/components/stat/classes/reports/hostsCommon.php',
		'./classes/components/stat/classes/reports/openstatAds.php',
		'./classes/components/stat/classes/reports/openstatCampaigns.php',
		'./classes/components/stat/classes/reports/openstatServices.php',
		'./classes/components/stat/classes/reports/openstatSources.php',
		'./classes/components/stat/classes/reports/pageNext.php',
		'./classes/components/stat/classes/reports/pagesHits.php',
		'./classes/components/stat/classes/reports/paths.php',
		'./classes/components/stat/classes/reports/refererByEntry.php',
		'./classes/components/stat/classes/reports/sectionHits.php',
		'./classes/components/stat/classes/reports/sourcesDomainsConcrete.php',
		'./classes/components/stat/classes/reports/sourcesDomains.php',
		'./classes/components/stat/classes/reports/sourcesSEOConcrete.php',
		'./classes/components/stat/classes/reports/sourcesSEOKeywordsConcrete.php',
		'./classes/components/stat/classes/reports/sourcesSEOKeywords.php',
		'./classes/components/stat/classes/reports/sourcesSEO.php',
		'./classes/components/stat/classes/reports/sourcesTop.php',
		'./classes/components/stat/classes/reports/tag.php',
		'./classes/components/stat/classes/reports/userStat.php',
		'./classes/components/stat/classes/reports/visitCommonHours.php',
		'./classes/components/stat/classes/reports/visitCommon.php',
		'./classes/components/stat/classes/reports/visitersCommonHours.php',
		'./classes/components/stat/classes/reports/visitersCommon.php',
		'./classes/components/stat/classes/reports/visitTime.php'
	];