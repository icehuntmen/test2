<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Шаблон вкладки "Модули" -->
	<xsl:template match="/result[@method = 'modules']/data">

		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />

		<div class="tabs-content module" data-is-last-version="{@is-last-version}">
			<div class="section selected">
				<div class="location">
					<div class="saveSize"/>
					<a class="btn-action loc-right infoblock-show">
						<i class="small-ico i-info"/>
						<xsl:text>&help;</xsl:text>
					</a>
				</div>
				<xsl:apply-templates select="document('udata://system/listErrorMessages')/udata/items" mode="config.error"/>
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="col-md-12">
								<table class="btable btable-striped bold-head">
									<thead>
										<th>
											<xsl:text>&module-list-available-for-installing;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-install;</xsl:text>
										</th>
									</thead>
									<tbody>
										<xsl:choose>
											<xsl:when test="available-module">
												<xsl:apply-templates select="available-module" mode="list-view"/>
											</xsl:when>
											<xsl:otherwise>
												<tr>
													<td>&all-available-modules-installed;</td>
													<td/>
												</tr>
											</xsl:otherwise>
										</xsl:choose>
									</tbody>
								</table>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<table class="btable btable-striped bold-head">
									<thead>
										<th>
											<xsl:text>&label-modules-list;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-delete;</xsl:text>
										</th>
									</thead>
									<tbody>
										<xsl:apply-templates select="module" mode="list-view"/>
									</tbody>
								</table>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<form action="{$lang-prefix}/admin/config/add_module_do/" enctype="multipart/form-data" method="post">
									<div class="field modules">
										<div>
											<div class="title-edit">
												<xsl:text>&label-install-path;</xsl:text>
											</div>
											<input value="classes/components/" class="default module-path" name="module_path"/>
											<input type="submit" class="btn color-blue install-btn" value="&label-install;"/>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
					<div class="column">
						<div class="infoblock">
							<h3>
								<xsl:text>&label-quick-help;</xsl:text>
							</h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="infoblock-hide"/>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script src="/styles/skins/modern/data/modules/config/ComponentInstaller.js?{$system-build}" />
	</xsl:template>

	<!-- Шаблон вкладки "Расширения" -->
	<xsl:template match="/result[@method = 'extensions']/data">

		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />

		<div class="tabs-content module" data-is-last-version="{@is-last-version}">
			<div class="section selected">
				<div class="location">
					<div class="saveSize"/>
					<a class="btn-action loc-right infoblock-show">
						<i class="small-ico i-info"/>
						<xsl:text>&help;</xsl:text>
					</a>
				</div>
				<xsl:apply-templates select="document('udata://system/listErrorMessages')/udata/items" mode="config.error"/>
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="col-md-12">
								<table class="btable btable-striped bold-head">
									<thead>
										<th>
											<xsl:text>&extension-list-available-for-installing;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-install;</xsl:text>
										</th>
									</thead>
									<tbody>
										<xsl:choose>
											<xsl:when test="available-extension">
												<xsl:apply-templates select="available-extension" mode="list-view"/>
											</xsl:when>
											<xsl:otherwise>
												<tr>
													<td>&all-available-extensions-installed;</td>
													<td/>
												</tr>
											</xsl:otherwise>
										</xsl:choose>
									</tbody>
								</table>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<table class="btable btable-striped bold-head">
									<thead>
										<th>
											<xsl:text>&label-extensions-list;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-delete;</xsl:text>
										</th>
									</thead>
									<tbody>
										<xsl:apply-templates select="installed-extension" mode="list-view"/>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="column">
						<div class="infoblock">
							<h3>
								<xsl:text>&label-quick-help;</xsl:text>
							</h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="infoblock-hide"/>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script src="/styles/skins/modern/data/modules/config/ComponentInstaller.js?{$system-build}" />
	</xsl:template>

	<!-- Шаблон для отображение списка ошибок -->
	<xsl:template match="udata[@module = 'system' and @method = 'listErrorMessages']/items" mode="config.error">
		<div class="column">
			<div id="errorList">
				<p class="error"><strong>&js-label-errors-found;</strong></p>
				<ol class="error">
					<xsl:apply-templates select="item" mode="config.error"/>
				</ol>
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон для отображения одной ошибки в списке -->
	<xsl:template match="items/item" mode="config.error">
		<li>
			<xsl:value-of select="." disable-output-escaping="yes"/>
		</li>
	</xsl:template>

	<!-- Шаблон строки в списке модулей, доступных для установки -->
	<xsl:template match="available-module" mode="list-view">
		<tr>
			<td>
				<a>
					<xsl:value-of select="@label" />
				</a>
			</td>
			<td class="center">
				<a data-component="{.}" title="&label-install;">
					<i class="small-ico i-upload"/>
				</a>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон строки в списке расширений, доступных для установки -->
	<xsl:template match="available-extension" mode="list-view">
		<tr>
			<td>
				<a>
					<xsl:value-of select="@label" />
				</a>
			</td>
			<td class="center">
				<a data-component="{.}" data-extension="1" title="&label-install;">
					<i class="small-ico i-upload"/>
				</a>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон вывода ошибки формирования списка доступных модулей или расширений -->
	<xsl:template match="available-module[@error]|available-extension[@error]" mode="list-view">
		<tr>
			<td>
				<p>&error-label-available-module-list;</p>
				<p>
					<xsl:value-of select="@error" disable-output-escaping="yes"/>
				</p>
			</td>
			<td class="center"/>
		</tr>
	</xsl:template>

	<!-- Шаблон строки в списке установленных модулей -->
	<xsl:template match="module" mode="list-view">
		<tr>
			<td>
				<a href="{$lang-prefix}/admin/{.}/">
					<xsl:value-of select="@label" />
				</a>
			</td>
			<td class="center">
				<a href="{$lang-prefix}/admin/config/del_module/{.}/" title="&label-delete;">
					<i class="small-ico i-remove"/>
				</a>
			</td>
		</tr>
	</xsl:template>

	<!-- Шаблон строки в списке установленных расширений -->
	<xsl:template match="installed-extension" mode="list-view">
		<tr>
			<td>
				<a>
					<xsl:value-of select="@label" />
				</a>
			</td>
			<td class="center">
				<a href="{$lang-prefix}/admin/config/deleteExtension/{.}/" title="&label-delete;">
					<i class="small-ico i-remove"/>
				</a>
			</td>
		</tr>
	</xsl:template>

</xsl:stylesheet>
