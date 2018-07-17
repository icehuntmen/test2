<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/" [
	<!ENTITY sys-module         'vote'>
	<!ENTITY sys-method-add     'add'>
	<!ENTITY sys-method-edit    'edit'>
	<!ENTITY sys-method-del     'del'>
	<!ENTITY sys-method-list    'tree'>
	<!ENTITY sys-method-acivity 'activity'>
	<!ENTITY sys-type-item      'poll'>
]>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:template match="/result[@module = 'vote' and @method = 'lists']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<div class="imgButtonWrapper" xmlns:umi="http://www.umi-cms.ru/TR/umi">
						<a id="addVote" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/0/"
						   class="type_select btn color-blue loc-left" umi:type="vote::poll">
							<xsl:text>&label-add-item;</xsl:text>
						</a>
					</div>
					<a class="btn-action loc-right infoblock-show"><i class="small-ico i-info"></i><xsl:text>&help;</xsl:text></a>
				</div>
				<div class="layout">
					<div class="column">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="content-type">pages</xsl:with-param>
							<xsl:with-param name="control-params">poll</xsl:with-param>
							<xsl:with-param name="flat-mode">1</xsl:with-param>
							<xsl:with-param name="js-add-buttons">
								createAddButton(
								$('#addVote')[0], oTable,
								'<xsl:value-of select="$lang-prefix"/>/admin/&sys-module;/&sys-method-add;/0/',
								[true, '*']
								);
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
