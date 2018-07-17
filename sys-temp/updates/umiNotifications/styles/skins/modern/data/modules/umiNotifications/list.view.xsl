<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'notifications']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<a class="btn-action loc-right infoblock-show">
						<i class="small-ico i-info"></i>
						<xsl:text>&help;</xsl:text>
					</a>
				</div>

				<div class="layout">
					<div class="column">
						<div id="tableWrapper"></div>
						<script src="/js/underscore-min.js"></script>
						<script src="/js/backbone-min.js"></script>
						<script src="/js/twig.min.js"></script>
						<script src="/js/backbone-relational.js"></script>
						<script src="/js/backbone.marionette.min.js"></script>
						<script src="/js/app.min.js"></script>
						<script>
							(function() {
							new umiDataController({
							container: '#tableWrapper',
							prefix: '<xsl:value-of select="$lang-prefix" />/admin/umiNotifications',
							module: 'umiNotifications',
							configUrl:'/admin/umiNotifications/flushDatasetConfiguration/.json',
							dataProtocol: 'json',
							domain:'<xsl:value-of select="$domain-id"/>',
							lang:'<xsl:value-of select="$lang-id"/>',
							<xsl:if test="$domainsCount > 1">
								domainsList:<xsl:apply-templates select="$domains-list" mode="ndc_domain_list"/>,
							</xsl:if>
							toolbarMenu: ['editButton'],
							perPageLimit: 50
							}).start();
							})();
						</script>
					</div>

					<div class="column">
						<div class="infoblock">
							<h3>
								<xsl:text>&label-quick-help;</xsl:text>
							</h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="infoblock-hide"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
