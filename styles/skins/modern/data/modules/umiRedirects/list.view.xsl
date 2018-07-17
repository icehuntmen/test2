<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'lists']/data[@type = 'list' and @action = 'view']">
		<script src="/styles/skins/modern/data/modules/umiRedirects/removeAllRedirects.js?{$system-build}" />

		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="imgButtonWrapper loc-left" style="bottom:0px;">
						<a id="addRedirectButton" class="btn color-blue loc-left"
						   href="{$lang-prefix}/admin/umiRedirects/add/">&label-button-add-redirect;</a>

						<a id="removeAllRedirectsButton" class="btn color-blue loc-left">
							&label-button-remove-all-redirects;
						</a>
					</div>

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
							(function(){
								new umiDataController({
								container:'#tableWrapper',
								prefix:'/admin/umiRedirects',
								module:'umiRedirects',
								controlParam:'',
								dataProtocol: 'json',
								domain:1,
								lang:1,
								configUrl:'/admin/umiRedirects/flushDataConfig/.json',
								debug:true
								}).start();
							})()
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
