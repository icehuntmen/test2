<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM  "ulang://common/catalog" [
		<!ENTITY sys-module       'filemanager'>
		<!ENTITY sys-method-add       'add_shared_file'>
		]>

<xsl:stylesheet version="1.0"
								xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
								xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="data" priority="1">
		<xsl:variable name="filemanager-id"
									select="document(concat('uobject://',/result/@user-id))/udata//property[@name = 'filemanager']/value/item/@id" />

		<xsl:variable name="filemanager">
			<xsl:choose>
				<xsl:when test="not($filemanager-id)">
					<xsl:text>elfinder</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="document(concat('uobject://',$filemanager-id))/udata//property[@name = 'fm_prefix']/value" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="tabs-content module">
			<div class="section selected">

				<div class="location">
					<div class="imgButtonWrapper loc-left" id="filemanager_upload_files">
						<a id="addFile" class="btn color-blue loc-left" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/">&label-add-file;</a>
						<a class="btn color-blue loc-left" href="javascript:void(0);"
							 umi:lang="{/result/@interface-lang}"
							 umi:filemanager="{$filemanager}"
						>&label-filemanager;</a>
					</div>
					<a class="btn-action loc-right infoblock-show">
						<i class="small-ico i-info"></i>
						<xsl:text>&help;</xsl:text>
					</a>
				</div>

				<div class="layout">
					<div class="column">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="js-add-buttons">
								createAddButton($('#addFile')[0], oTable, '{$pre_lang}/admin/&sys-module;/&sys-method-add;/', ['file', true]);
							</xsl:with-param>
							<xsl:with-param name="allow-drag">1</xsl:with-param>
						</xsl:call-template>
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
