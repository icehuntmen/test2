<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="data[@type = 'list' and @action = 'modify']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<div class="saveSize"></div>
					<a class="btn-action loc-right infoblock-show">
						<i class="small-ico i-info"></i>
						<xsl:text>&help;</xsl:text>
					</a>
				</div>

				<div class="layout">
					<div class="column">

						<form action="do/" method="post" enctype="multipart/form-data">
							<table class="btable btable-striped">
								<thead>
									<tr>
										<th>
											<xsl:text>&label-name;</xsl:text>
										</th>

										<th>
											<xsl:text>&label-module;</xsl:text>
										</th>

										<th>
											<xsl:text>&label-method;</xsl:text>
										</th>

										<th>
											<xsl:text>&label-delete;</xsl:text>
										</th>
									</tr>
								</thead>
								<tbody>
									<xsl:apply-templates mode="list-modify" />
									<tr>
										<td>
											<input type="text" class="default" name="data[new][title]" />
										</td>

										<td>
											<input type="text" class="default" name="data[new][module]" />
										</td>

										<td>
											<input type="text" class="default" name="data[new][method]" />
										</td>

										<td />
									</tr>
								</tbody>
							</table>
							<div class="row">
								<div id="buttons_wr" class="col-md-12">

									<xsl:call-template name="std-save-button" />

								</div>
							</div>
						</form>

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

	<xsl:template match="/result[@method='guide_items']/data[@type = 'list' and @action = 'modify']">
		<div class="location">
			<div class="imgButtonWrapper">
				<a class="smc-fast-add btn color-blue" href="{$lang-prefix}/admin/data/guide_item_add/{$param0}/"
					 ref="tree-data-guide_items">
					<xsl:text>&label-guide-item-add;</xsl:text>
				</a>
			</div>
		</div>

		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="control-params" select="$param0" />
			<xsl:with-param name="content-type" select="'objects'" />
		</xsl:call-template>
	</xsl:template>
</xsl:stylesheet>
