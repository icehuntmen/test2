<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/news">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="data" priority="1">
		<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">

			<div class="imgButtonWrapper" xmlns:umi="http://www.umi-cms.ru/TR/umi">
				<a id="addCategory" href="{$lang-prefix}/admin/news/add/{$param0}/rubric/" class="btn color-blue loc-left" umi:type="news::rubric">
					<xsl:text>&label-add-list;</xsl:text>
				</a>

				<a id="addObject" href="{$lang-prefix}/admin/news/add/{$param0}/item/" class="btn color-blue loc-left" umi:type="news::item">
					<xsl:text>&label-add-item;</xsl:text>
				</a>
			</div>
			<a class="btn-action loc-right infoblock-show"><i class="small-ico i-info"></i><xsl:text>&help;</xsl:text></a>
		</div>

		<div class="layout">
			<div class="column">

				<xsl:call-template name="ui-smc-table">
					<xsl:with-param name="js-add-buttons"><![CDATA[
						createAddButton($('#addCategory')[0], oTable, '{$pre_lang}/admin/news/add/{id}/rubric/', ['rubric', true]);
						createAddButton($('#addObject')[0], oTable, '{$pre_lang}/admin/news/add/{id}/item/', ['rubric']);
					]]></xsl:with-param>

					<xsl:with-param name="allow-drag">1</xsl:with-param>
				</xsl:call-template>

			</div>
			<div class="column">
				<div  class="infoblock">
					<h3><xsl:text>&label-quick-help;</xsl:text></h3>
					<div class="content" title="{$context-manul-url}">
					</div>
					<div class="infoblock-hide"></div>
				</div>
			</div>
		</div>


	</xsl:template>
</xsl:stylesheet>