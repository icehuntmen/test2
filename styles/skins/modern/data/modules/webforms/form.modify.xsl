<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common"[
	<!ENTITY sys-module 'data'>
	<!ENTITY sys-module 'webforms'>
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[not(@method = 'form_edit' or @method = 'form_add')]/data[@type = 'form' and (@action = 'modify' or @action = 'create') and object]">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="saveSize"></div>
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<input type="hidden" name="referer" value="{/result/@referer-uri}" />
							<input type="hidden" name="domain" value="{$domain-floated}" />

							<xsl:apply-templates select="group" mode="form-modify" />

							<xsl:apply-templates select="object/properties/group" mode="form-modify">
								<xsl:with-param name="show-name">0</xsl:with-param>
							</xsl:apply-templates>

							<div class="row">
								<div id="buttons_wr" class="col-md-12">
									<xsl:choose>
										<xsl:when test="$data-action = 'create'">
											<xsl:call-template name="std-form-buttons-add" />
										</xsl:when>
										<xsl:otherwise>
											<xsl:call-template name="std-form-buttons" />
										</xsl:otherwise>
									</xsl:choose>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<xsl:call-template name="error-checker" />
		<script src="/styles/skins/modern/data/modules/webforms/initWebformsErrorChecker.js?{$system-build}" />
	</xsl:template>

	<xsl:template match="properties/group" mode="form-modify">
		<div class="panel-settings" name="g_{@name}">
			<a data-name="{@name}" data-label="{$title}"></a>

			<div class="title">
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="@name" />
				</xsl:call-template>
				<div class="round-toggle"></div>
				<h3>
					<xsl:value-of select="@title" />
				</h3>
			</div>

			<div class="content">
				<div class="layout">
					<div class="column">
						<div class="row">
							<xsl:if test="position() = 1 and not(/result/@method='template_add') and not(/result/@method='template_edit')">
								<div class="col-md-6">
									<div class="title-edit">
										<xsl:text>&label-name;</xsl:text>
									</div>
									<span>
										<input class="default" type="text" name="name" value="{../../@name}" />
									</span>
								</div>
							</xsl:if>

							<xsl:apply-templates select="field" mode="form-modify" />
						</div>
					</div>

					<div class="column">
						<div class="infoblock">
							<h3>
								<xsl:text>&type-edit-tip;</xsl:text>
							</h3>
							<div class="content" />
							<div class="group-tip-hide" />
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'form_edit' or @method = 'form_add']/data[@type = 'form' and (@action = 'modify' or @action = 'create')]">
		<form action="do/" method="post" enctype="multipart/form-data">
			<input type="hidden" name="referer" value="{/result/@referer-uri}" />

			<xsl:apply-templates select="type" mode="fieldgroup-common" />

			<div class="row">
				<div id="buttons_wr" class="col-md-12">
					<xsl:choose>
						<xsl:when test="$data-action = 'create'">
							<xsl:call-template name="std-form-buttons-add" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:call-template name="std-form-buttons" />
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</div>
		</form>

		<xsl:if test="$data-action = 'modify'">
			<xsl:apply-templates select="//fieldgroups" mode="fieldsgroups-other" />
		</xsl:if>
	</xsl:template>

	<xsl:template match="type" mode="fieldgroup-common">
		<xsl:variable name="form_id">
			<xsl:choose>
				<xsl:when test="@id">
					<xsl:value-of select="@id" />
				</xsl:when>
				<xsl:when test="string(number(text())) != 'NaN'">
					<xsl:value-of select="text()" />
				</xsl:when>
			</xsl:choose>
		</xsl:variable>
		<div class="panel-settings" name="g_form">
			<summary class="group-tip">
				<xsl:text>Основные настройки формы обратной связи.</xsl:text>
			</summary>
			<a data-name="{@name}" data-label="{$title}"></a>
			<div class="title">
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="'g_form'" />
					<xsl:with-param name="force-show" select="1" />
				</xsl:call-template>
				<div class="round-toggle"></div>
				<h3><xsl:text>&label-form;</xsl:text></h3>
			</div>
			<div class="content">
				<div class="layout">
					<div class="column">
						<div class="row">
							<div class="col-md-6">
								<div class="title-edit">
									<xsl:text>&label-form-name;</xsl:text>
								</div>
								<span>
									<input class="default" type="text" name="data[name]" value="{@title}"/>
								</span>
							</div>
							<xsl:apply-templates select="document(concat('udata://webforms/getAddresses/', $form_id))/udata"/>
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

	<xsl:template match="udata[@module = 'webforms'][@method = 'getAddresses']">
		<div class="col-md-6">
			<div class="title-edit">
				<xsl:text>&label-address-send;</xsl:text>
			</div>
			<div class="">
				<select class="default newselect" name="{@input_name}" id="relationSelect{generate-id()}">
					<xsl:apply-templates select="." mode="required_attr" />
					<xsl:if test="@multiple = 'multiple'">
						<xsl:attribute name="multiple">multiple</xsl:attribute>
						<xsl:attribute name="style">height: 62px;</xsl:attribute>
					</xsl:if>
					<option value=""></option>
					<xsl:apply-templates select="items/item" />
				</select>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'webforms' and @method = 'getAddresses']/items/item">
		<option value="{@id}">
			<xsl:value-of select="." />
		</option>
	</xsl:template>
	
	<xsl:template match="udata[@module = 'webforms' and @method = 'getAddresses']/items/item[@selected = 'selected']">
		<option value="{@id}" selected="selected">
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="base" mode="fieldsgroups-other">
		<div class="header">
			<span><xsl:value-of select="." /></span>
			<div class="l" /><div class="r" />
		</div>
	</xsl:template>

	<xsl:template match="fieldgroups" mode="fieldsgroups-other" >
		<div class="panel-settings">
			<div class="title"></div>
			<div class="content">
				<script src="/styles/skins/modern/design/js/type.control.js?{$system-build}"></script>
				<div id="group-fields">
					<div id="groupsContainer" class="content">
						<div class='row buttons'>
							<a href='#' class='add_group btn color-blue'>
								<xsl:text>&type-edit-add_group;</xsl:text>
							</a>
						</div>

						<div class="row">
							<div class="col-md-12">
								<xsl:apply-templates select="group" mode="fieldsgroups-tpl"/>
							</div>
						</div>
					</div>
				</div>
				<script type="text/javascript">
					var modernCurentType = <xsl:value-of select="//type/@id" />;
					modernTypeController.init(modernCurentType,{
					<xsl:apply-templates select="group[not(@locked)]" mode="groupsmodel-tpl"/>
					});
				</script>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group" mode="groupsmodel-tpl">
		"<xsl:value-of select="@id"/>":{
		"id":<xsl:value-of select="@id"/>,
		"title":"<xsl:value-of select="@title"/>",
		"name":"<xsl:value-of select="@name"/>",
		"visible":"<xsl:choose><xsl:when test="@visible = 'visible'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>"
		}
		<xsl:if test="not(position() = last())">,</xsl:if>
	</xsl:template>

	<xsl:template match="group" mode="fieldsgroups-tpl">
		<div umigroupid="{@id}">
			<xsl:attribute name="class">
				<xsl:text>fg_container</xsl:text>
				<xsl:if test="@locked = 'locked'">
					<xsl:text> locked</xsl:text>
				</xsl:if>
				<xsl:if test="not(@visible = 'visible')">
					<xsl:text> finvisible</xsl:text>
				</xsl:if>
			</xsl:attribute>
			<div class="fg_container_header">
				<span id="headg{@id}title" class="left">
					<xsl:value-of select="@title" /> [<xsl:value-of select="@name" />]
				</span>
				<span id="g{@id}control">
					<xsl:if test="not(@locked = 'locked')">
						<a class="gedit" data="{@id}"  title="&label-edit;"><i class="small-ico i-edit"></i></a>
						<a class="gremove" data="{@id}" title="&label-delete;"><i class="small-ico i-remove"></i></a>
					</xsl:if>
				</span>
				<span id="g{@id}save" style="display:none;">

				</span>
			</div>
			<div class="group_edit" style="display:none;"></div>
			<div class="fg_container_body content">
				<ul class="fg_container ui-sortable" umigroupid="{@id}">
					<xsl:if test="not(@locked = 'locked')">
						<div class="buttons" style="padding-bottom: 10px;">
							<a data="{@id}" class='fadd btn color-blue'>
								<xsl:text>&type-edit-add_field;</xsl:text>
							</a>
						</div>
					</xsl:if>
					<xsl:apply-templates select="field" mode="fieldsgroups-tpl" />
				</ul>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="field" mode="fieldsgroups-tpl">
		<li umifieldid="{@id}">
			<xsl:attribute name="class">
				<xsl:text>f_container</xsl:text>
				<xsl:if test="not(@visible = 'visible')">
					<xsl:text> finvisible</xsl:text>
				</xsl:if>
				<xsl:if test="@locked = 'locked'">
					<xsl:text> locked</xsl:text>
				</xsl:if>
			</xsl:attribute>
			<div class="row">
				<div class="view col-md-12">
					<span id="headf{@id}title" class="col-md-3" style="overflow:hidden; white-space:nowrap; text-overflow: ellipsis;">
						<xsl:value-of select="@title"/>
						<xsl:if test="@required = 'required'"> *</xsl:if>
					</span>
					<span id="headf{@id}name" class="col-md-3" style="overflow:hidden; white-space:nowrap; text-overflow: ellipsis;">
						[<xsl:value-of select="@name"/>]
					</span>
					<span id="headf{@id}type" class="col-md-4" style="overflow:hidden; white-space:nowrap; text-overflow: ellipsis;">
						(<xsl:value-of select="./type/@name"/>)
					</span>
					<span id="f{@id}save" class="col-md-2" style="display:none;"></span>
					<span id="f{@id}control" class="pull-right">
						<xsl:if test="not(@locked = 'locked')">
							<a class="fedit" data="{@id}" title="&label-edit;"><i class="small-ico i-edit"></i></a>
							<a class="fremove" data="{@id}" title="&label-delete;"><i class="small-ico i-remove"></i></a>
						</xsl:if>
					</span>
				</div>
				<div class="edit col-md-12" style="display:none;">
					<xsl:if test="not(@locked = 'locked' )">
						<form>
							<div class="row">
								<div class="col-md-6">
									<div class="title-edit" >
										<xsl:text>&label-name;</xsl:text>
									</div>
									<input type='text' class="default" id="{@id}title" name='data[title]' value="{@title}" />
								</div>
								<div class="col-md-6">
									<div class="title-edit">
										<xsl:text>&type-edit-name;</xsl:text>
									</div>
									<input type='text' class="default" id='{@id}name' name='data[name]' value='{@name}' />
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<div class="title-edit" >
										<xsl:text>&type-edit-tip;</xsl:text>
									</div>
									<input type='text' class="default" id="{@id}tip" name='data[tip]' value="{./tip/text()}" />
								</div>
								<div class="col-md-6">
									<div class="title-edit">
										<xsl:text>&label-datatype;</xsl:text>
									</div>
									<select id='{@id}type' name='data[field_type_id]'>
										<option value="{@field-type-id}">
											<xsl:value-of select="./type/@name" />
										</option>
									</select>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<div class="title-edit">
										<xsl:text>&type-edit-restriction;</xsl:text>
									</div>
									<select id='{@id}restriction' name='data[restriction_id]'>
									</select>
								</div>
								<div class="col-md-6" id="{@id}guideCont" style="display:none;">
									<div class="title-edit">
										<xsl:text>&js-type-edit-guide;</xsl:text>
									</div>
									<select id='{@id}guide' name='data[guide_id]'></select>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<label class="checkbox-wrapper">
										<div class="checkbox">
											<xsl:if test="@visible = 'visible'">
												<xsl:attribute name="class">checkbox checked</xsl:attribute>
											</xsl:if>
											<input type="hidden" name="data[is_visible]" value="1">
												<xsl:if test="@visible = 'visible'">
													<xsl:attribute name="checked">checked</xsl:attribute>
												</xsl:if>
											</input>
											<input type="checkbox" id="{@id}visible" name="data[is_visible]" value="1" class="checkbox">
												<xsl:if test="@visible = 'visible'">
													<xsl:attribute name="checked">checked</xsl:attribute>
												</xsl:if>
											</input>
										</div>
										<span>
											<xsl:text>&js-type-edit-visible;</xsl:text>
										</span>
									</label>
								</div>
								<div class="col-md-6">
									<label class="checkbox-wrapper">
										<div class="checkbox">
											<xsl:if test="@indexable = 'indexable'">
												<xsl:attribute name="class">checkbox checked</xsl:attribute>
											</xsl:if>
											<input type="hidden" name="data[in_search]" value="1">
												<xsl:if test="@indexable = 'indexable'">
													<xsl:attribute name="checked">checked</xsl:attribute>
												</xsl:if>
											</input>
											<input type="checkbox" id="{@id}indexable" name="data[in_search]" value="1" class="checkbox">
												<xsl:if test="@indexable = 'indexable'">
													<xsl:attribute name="checked">checked</xsl:attribute>
												</xsl:if>
											</input>
										</div>
										<span>
											<xsl:text>&js-type-edit-indexable;</xsl:text>
										</span>
									</label>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<label class="checkbox-wrapper">
										<div class="checkbox">
											<xsl:if test="@required = 'required'">
												<xsl:attribute name="class">checkbox checked</xsl:attribute>
											</xsl:if>
											<input type="checkbox" id="{@id}required" name="data[is_required]" value="1" class="checkbox">
												<xsl:if test="@required = 'required'">
													<xsl:attribute name="checked">checked</xsl:attribute>
												</xsl:if>
											</input>
										</div>
										<span>
											<xsl:text>&js-type-edit-required;</xsl:text>
										</span>
									</label>
								</div>
								<div class="col-md-6">
									<label class="checkbox-wrapper">
										<div class="checkbox">
											<xsl:if test="@filterable = 'filterable'">
												<xsl:attribute name="class">checkbox checked</xsl:attribute>
											</xsl:if>
											<input type="checkbox" id="{@id}filterable" name="data[in_filter]" value="1" class="checkbox">
												<xsl:if test="@filterable = 'filterable'">
													<xsl:attribute name="checked">checked</xsl:attribute>
												</xsl:if>
											</input>
										</div>
										<span>
											<xsl:text>&js-type-edit-filterable;</xsl:text>
										</span>
									</label>
								</div>
							</div>
							<div class="row" style="padding-bottom: 10px;">
								<div class='pull-right buttons' style="">
									<input type='button' value="&save;" data="{@id}" class="fsave btn color-blue"/>
									<input type='button' value="&js-filemanager-cancel;" data="{@id}" class='fcancel btn color-blue'/>
								</div>
							</div>
						</form>
					</xsl:if>

				</div>
			</div>
		</li>
	</xsl:template>

	<xsl:template match="group" mode="fieldsgroups-other">
		type.addGroup({id    : <xsl:value-of select="@id" />,
					   title : '<xsl:value-of select="@title" />',
					   name  : '<xsl:value-of select="@name" />',
					   visible : <xsl:choose><xsl:when test="@visible = 'visible'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>,
					   locked  : <xsl:choose><xsl:when test="@locked = 'locked'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>});

		<xsl:apply-templates select="field" mode="fieldsgroups-other" />
	</xsl:template>

	<xsl:template match="field" mode="fieldsgroups-other">
		type.addField(<xsl:value-of select="../@id" />,
					 {id       : <xsl:value-of select="@id" />,
					  title    : '<xsl:value-of select="@title" />',
					  name     : '<xsl:value-of select="@name" />',
					  tip      : '<xsl:value-of select="./tip/text()" />',
					  typeId   : <xsl:value-of select="@field-type-id" />,
					  typeName : '<xsl:value-of select="./type/@name" />',
					  <xsl:if test="./@guide-id">
					  guideId  : <xsl:value-of select="@guide-id" />,
					  </xsl:if>
					  <xsl:if test="./restriction">
					  restrictionId    : <xsl:value-of select="./restriction/@id" />,
					  restrictionTitle : '<xsl:value-of select="./restriction/text()" />',
					  </xsl:if>
					  visible    : <xsl:choose><xsl:when test="@visible  = 'visible'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>,
					  required   : <xsl:choose><xsl:when test="@required = 'required'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>,
					  filterable : <xsl:choose><xsl:when test="@filterable = 'filterable'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>,
					  indexable  : <xsl:choose><xsl:when test="@indexable  = 'indexable'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>,
					  locked  : <xsl:choose><xsl:when test="@locked = 'locked'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>});
	</xsl:template>



	<xsl:template match="group[@name = 'SendingData']" mode="fieldsgroups-other" />
	<xsl:template match="group[@name = 'Binding' or @name = 'binding']"     mode="form-modify" />

	<xsl:template match="group[@name = 'BindToForm']" mode="form-modify">
		<div class="panel-settings">
			<a data-name="{@name}" data-label="{$title}"></a>
			<div class="title">
				<div class="round-toggle"></div>
				<h3>
					<xsl:value-of select="@title" />
				</h3>
			</div>
			<div class="content">
				<div class="row">
					<div class="col-md-6">
						<div class="title-edit">
								<xsl:text>&label-form;</xsl:text>
						</div>
						<span>
							<select name="system_form_id" class="default newselect" id="system_form_id">
								<xsl:apply-templates
										select="document(concat('udata://webforms/getUnbindedForms/', //object/@id))/udata/items/item"
										mode="getUnbindedForms">
									<xsl:with-param name="selected_id" select="@selected_type"/>
								</xsl:apply-templates>
							</select>
						</span>
					</div>
				</div>


			</div>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="getUnbindedForms">
		<xsl:param name="selected_id" />
		<option value="{@id}">
			<xsl:if test="@id = $selected_id">
				<xsl:attribute name="selected"><xsl:text>selected</xsl:text></xsl:attribute>
			</xsl:if>
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="field" mode="field-tpl">

	</xsl:template>

</xsl:stylesheet>
