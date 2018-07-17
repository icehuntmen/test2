<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common"[
	<!ENTITY sys-module 'banners'>	
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">
	
	<xsl:template match="group[@name='common']" mode="form-modify">
		<xsl:param name="show-name"><xsl:text>1</xsl:text></xsl:param>
		<xsl:param name="show-type"><xsl:text>1</xsl:text></xsl:param>
	
		<div class="panel-settings" name="g_{@name}">
			<a data-name="{@name}" data-label="{@title}"></a>
			<div class="title">
				<xsl:call-template name="group-tip" />

				<div class="round-toggle"></div>
				<h3>
					<xsl:value-of select="@title" />
				</h3>
			</div>
			
			<div class="content">
				<div class="layout">
					<div class="column">
						<div class="row">
							<xsl:apply-templates select="." mode="form-modify-group-fields">
								<xsl:with-param name="show-name" select="$show-name" />
								<xsl:with-param name="show-type" select="$show-type" />
							</xsl:apply-templates>

							<xsl:call-template name="calculate-ctr" />
						</div>
					</div>
					<div class="column">
						<div  class="infoblock">
							<h3>
								<xsl:text>&type-edit-tip;</xsl:text>
							</h3>
							<div class="content" >
							</div>
							<div class="group-tip-hide"></div>
						</div>
					</div>

				</div>
			</div>
		</div>
	</xsl:template>
	
	<xsl:template name="calculate-ctr">
		<xsl:variable name="group" select="/result/data/object/properties/group[@name = 'view_params']" />
		<xsl:variable name="views-count" select="$group/field[@name = 'views_count']" />
		<xsl:variable name="clicks-count" select="$group/field[@name = 'clicks_count']" />
		<div class="col-md-6">

				<div class="title-edit">
					<acronym>
						<xsl:attribute name="title"><xsl:text>&ctr-description;</xsl:text></xsl:attribute>
						<xsl:attribute name="class"><xsl:text>acr</xsl:text></xsl:attribute>
						<xsl:text>CTR</xsl:text>
					</acronym>					
				</div>
				<span>
					<xsl:value-of select="format-number(translate(number($clicks-count) div number($views-count), 'Na', '00'), '0.####%')" />					
				</span>

		</div>		
	</xsl:template>
	
</xsl:stylesheet>