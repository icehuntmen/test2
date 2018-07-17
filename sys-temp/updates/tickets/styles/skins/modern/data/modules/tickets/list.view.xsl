<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'tickets']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="save_size"></div>
					<a class="btn-action loc-right infoblock-show">
						<i class="small-ico i-info"></i>
						<xsl:text>&help;</xsl:text>
					</a>
				</div>
				<div class="layout">
					<div class="column">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="content-type">objects</xsl:with-param>
							<xsl:with-param name="control-params">tickets</xsl:with-param>
							<xsl:with-param name="js-ignore-props-edit">['message', 'url', 'user_id', 'create_time']
							</xsl:with-param>
						</xsl:call-template>
					</div>
					<div class="column">
						<div  class="infoblock">
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