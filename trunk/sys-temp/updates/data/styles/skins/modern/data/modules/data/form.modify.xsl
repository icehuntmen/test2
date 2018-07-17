<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common"[
		<!ENTITY sys-module        'data'>
		]>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

	<xsl:param name="skip-lock"/>

	<!--Form modify-->

	<xsl:template match="/result[@method = 'type_edit']/data[@type = 'form' and (@action = 'modify' or @action = 'create')]">
		<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<a class="btn-action loc-right infoblock-show">
				<i class="small-ico i-info"/>
				<xsl:text>&help;</xsl:text>
			</a>
		</div>
		<div class="layout">
			<div class="column">
				<form action="do/" method="post" enctype="multipart/form-data">
					<input type="hidden" name="referer" value="{/result/@referer-uri}"/>
					<xsl:apply-templates select="type" mode="fieldgroup-common"/>
					<div class="row">
						<xsl:call-template name="std-form-buttons" />
					</div>
				</form>
				<xsl:apply-templates select="//fieldgroups" mode="fieldsgroups-other"/>
			</div>
			<div class="column">
				<div  class="infoblock">
					<h3>
						<xsl:text>&label-quick-help;</xsl:text>
					</h3>
					<div class="content" title="{$context-manul-url}">
					</div>
					<div class="infoblock-hide"/>
				</div>
			</div>
		</div>
    </xsl:template>

	<xsl:template match="type" mode="fieldgroup-common">
		<div class="panel-settings">
			<div class="title" title='&label-name;: "{@title}"'>
				<h3>
					&label-edit-type-common;
				</h3>
			</div>
			<div id="group-common"  class="content">
				<div class="row">
					<div class="col-md-6">
						<label>
							<div class="title-edit">&label-type-name;</div>
							<div>
								<input type="text" class="default" name="data[name]" value="{@title}">
									<xsl:if test="./@locked = 'locked' and not($skip-lock = 1)">
										<xsl:attribute name="disabled">disabled</xsl:attribute>
									</xsl:if>
								</input>
							</div>
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<label>
							<div class="title-edit">
								<xsl:text>&field-domain_name;</xsl:text>
							</div>
							<xsl:variable name="domain-id" select="@domain-id"/>
							<div>
								<select class="default newselect" name="data[domain_id]" value="{$domain-id}">
									<xsl:if test="./@locked = 'locked' and not($skip-lock = 1)">
										<xsl:attribute name="disabled">disabled</xsl:attribute>
									</xsl:if>
									<xsl:choose>
										<xsl:when test="$domain-id &gt; 0">
											<option value="0">&label-for-all;</option>
											<option value="{$domain-id}" selected="selected">
												<xsl:value-of select="$domains-list/domain[@id = $domain-id]/@host" />
											</option>
										</xsl:when>
										<xsl:otherwise>
											<option value="0" selected="selected">&label-for-all;</option>
										</xsl:otherwise>
									</xsl:choose>
									<xsl:apply-templates select="$domains-list" mode="domain_id">
										<xsl:with-param name="selected.id" select="$domain-id" />
									</xsl:apply-templates>
								</select>
							</div>
						</label>
					</div>
					<div class="col-md-6">
						<label>
							<div class="title-edit">
								<xsl:text>&label-hierarchy-type;</xsl:text>
							</div>
							<xsl:variable name="base-id" select="base/@id"/>
							<div>
								<!-- I will not give you normal type for creating ;( -->
								<select class="default newselect" name="data[hierarchy_type_id]" value="{base/@id}">
									<xsl:if test="./@locked = 'locked' and not($skip-lock = 1)">
										<xsl:attribute name="disabled">disabled</xsl:attribute>
									</xsl:if>
									<option/>
									<xsl:apply-templates
											select="document('udata://system/hierarchyTypesList')/udata/items/item"
											mode="std-form-item">
										<xsl:with-param name="value" select="base/@id"/>
									</xsl:apply-templates>
								</select>
							</div>
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<label class="checkbox-wrapper">
							<input type="hidden" name="data[is_public]" value="0"/>
							<div class="checkbox">
								<xsl:if test="@public">
									<xsl:attribute name="checked">checked</xsl:attribute>
								</xsl:if>
								<input type="checkbox" name="data[is_public]" value="1" class="checkbox">
									<xsl:if test="@public">
										<xsl:attribute name="checked">checked</xsl:attribute>
									</xsl:if>
								</input>
							</div>
							<span>
								<xsl:text>&label-is-public;</xsl:text>
							</span>
						</label>
					</div>
					<div class="col-md-6">
						<label class="checkbox-wrapper">
							<input type="hidden" name="data[is_guidable]" value="0"/>
							<div class="checkbox">
								<xsl:if test="@guide">
									<xsl:attribute name="checked">checked</xsl:attribute>
								</xsl:if>
								<input type="checkbox" name="data[is_guidable]" value="1" class="checkbox">
									<xsl:if test="@guide">
										<xsl:attribute name="checked">checked</xsl:attribute>
									</xsl:if>
								</input>
							</div>
							<span>
								<xsl:text>&label-is-guide;</xsl:text>
							</span>
						</label>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="fieldgroups" mode="fieldsgroups-other" >
        <script src="/styles/skins/modern/design/js/type.control.js?{$system-build}"/>
		<div id="group-fields">
			<div id="groupsContainer" class="content">

					<div class="buttons row">
						<div class="col-md-12">
							<a href='#' class='add_group btn color-blue'>
								<xsl:text>&type-edit-add_group;</xsl:text>
							</a>
						</div>

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
	</xsl:template>

	<xsl:template match="group" mode="fieldsgroups-tpl">
		<div umigroupid="{@id}">
			<xsl:attribute name="class">
				<xsl:text>fg_container</xsl:text>
				<xsl:if test="@locked = 'locked' and not($skip-lock = 1)">
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
					<xsl:if test="not(@locked = 'locked'  and not($skip-lock))">
						<a class="gedit" data="{@id}"  title="&label-edit;"><i class="small-ico i-edit"/></a>
						<a class="gremove" data="{@id}" title="&label-delete;"><i class="small-ico i-remove"/></a>
					</xsl:if>
				</span>
				<span id="g{@id}save" style="display:none;">

				</span>
			</div>
			<div class="group_edit" style="display:none;"/>
			<div class="fg_container_body content">
				<ul class="fg_container ui-sortable" umigroupid="{@id}">
					<xsl:if test="not(@locked = 'locked') or $skip-lock">
						<div class="buttons" style="padding-bottom: 10px;">
							<a data="{@id}" class='fadd btn color-blue'>
								<xsl:text>&type-edit-add_field;</xsl:text>
							</a>
						</div>
					</xsl:if>
					<xsl:apply-templates select="field" mode="field-tpl" />
				</ul>
			</div>
		</div>
	</xsl:template>

	<xsl:template name="string-replace-all">
		<xsl:param name="text" />
		<xsl:param name="replace" />
		<xsl:param name="by" />
		<xsl:choose>
			<xsl:when test="$text = '' or $replace = ''or not($replace)" >
				<!-- Prevent this routine from hanging -->
				<xsl:value-of select="$text" />
			</xsl:when>
			<xsl:when test="contains($text, $replace)">
				<xsl:value-of select="substring-before($text,$replace)" />
				<xsl:value-of select="$by" />
				<xsl:call-template name="string-replace-all">
					<xsl:with-param name="text" select="substring-after($text,$replace)" />
					<xsl:with-param name="replace" select="$replace" />
					<xsl:with-param name="by" select="$by" />
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$text" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

    <xsl:template match="group" mode="groupsmodel-tpl">
		<xsl:variable name="tip">
			<xsl:call-template name="string-replace-all">
				<xsl:with-param name="text" select="tip" />
				<xsl:with-param name="replace" select="'&quot;'" />
				<xsl:with-param name="by" select="'\&quot;'" />
			</xsl:call-template>
		</xsl:variable>

            "<xsl:value-of select="@id"/>":{
                "id":<xsl:value-of select="@id"/>,
                "title":"<xsl:value-of select="@title"/>",
                "name":"<xsl:value-of select="@name"/>",
                <!-- "tip":"<xsl:value-of select="$tip" />", -->
                "visible":"<xsl:choose><xsl:when test="@visible = 'visible'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>"
            }
            <xsl:if test="not(position() = last())">,</xsl:if>
    </xsl:template>



	<xsl:template match="field" mode="field-tpl">
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
					<span id="headf{@id}title" class="col-md-3 field-title" title="{@title}">
						<xsl:value-of select="@title"/>
						<xsl:if test="@required = 'required'"> *</xsl:if>
					</span>
					<span id="headf{@id}name" class="col-md-3 field-name" title="{@name}">
						[<xsl:value-of select="@name"/>]
					</span>
					<span id="headf{@id}type" class="col-md-4 field-type" title="{./type/@name}">
						(<xsl:value-of select="./type/@name"/>)
					</span>
					<span id="f{@id}save" class="col-md-2" style="display:none;"/>
					<span id="f{@id}control" class="pull-right">
						<xsl:if test="not(@locked = 'locked' and not($skip-lock))">
							<a class="fedit" data="{@id}" title="&label-edit;"><i class="small-ico i-edit"/></a>
							<a class="fremove" data="{@id}" title="&label-delete;"><i class="small-ico i-remove"/></a>
						</xsl:if>
					</span>
				</div>
				<div class="edit col-md-12" style="display:none;">
					<xsl:if test="not(@locked = 'locked'  and not($skip-lock) )">
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
									<select id='{@id}guide' name='data[guide_id]'/>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<div class="checkbox-wrapper">
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
									</div>
								</div>
								<div class="col-md-6">
									<div class="checkbox-wrapper">
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
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<div class="checkbox-wrapper">
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
									</div>
								</div>
								<div class="col-md-6">
									<div class="checkbox-wrapper">
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
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<div class="checkbox-wrapper">
										<div class="checkbox">
											<xsl:if test="@important = 'important'">
												<xsl:attribute name="class">checkbox checked</xsl:attribute>
											</xsl:if>
											<input type="checkbox" id="{@id}important" name="data[is_important]" value="1" class="checkbox">
												<xsl:if test="@important = 'important'">
													<xsl:attribute name="checked">checked</xsl:attribute>
												</xsl:if>
											</input>
										</div>
										<span>
											<xsl:text>&js-type-edit-important;</xsl:text>
										</span>
									</div>
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
					   locked  : <xsl:choose><xsl:when test="@locked = 'locked' and not($skip-lock)">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>});
					   
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
					  locked  : <xsl:choose><xsl:when test="@locked = 'locked' and not($skip-lock)">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>});
	</xsl:template>

	<xsl:template match="object[not(./properties/group)]" mode="form-modify">
		<div class="panel properties-group">
			<div class="header">
				<span><xsl:text>&nbsp;</xsl:text></span>
				<div class="l" /><div class="r" />
			</div>
			<div class="content">
				<xsl:call-template name="std-form-name">
					<xsl:with-param name="value" select="@name" />
					<xsl:with-param name="show-tip"><xsl:text>0</xsl:text></xsl:with-param>
				</xsl:call-template>
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
	</xsl:template>

</xsl:stylesheet>
