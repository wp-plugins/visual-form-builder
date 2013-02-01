jQuery(document).ready(function(f){if(pagenow=="toplevel_page_visual-form-builder"){f(".if-js-closed").removeClass("if-js-closed").addClass("closed");postboxes.add_postbox_toggles("toplevel_page_visual-form-builder")}var a=null;f(document).on("mouseenter mouseleave",".vfb-tooltip",function(l){if(l.type=="mouseenter"){if(a){clearTimeout(a);a=null}var i=f(this).attr("title"),k=f(this).attr("rel"),j=f(this).width();f(this).append('<div class="tooltip"><h3>'+i+'</h3><p class="text">'+k+"</p></div>");f.data(this,"title",i);this.title="";f(this).find(".tooltip").css({left:j+22});a=setTimeout(function(){f(".tooltip").fadeIn(300)},500)}else{this.title=f.data(this,"title");f(".tooltip").fadeOut(500);f(this).children().remove()}});f(document).on("click","a.addOption",function(o){o.preventDefault();var j=f(this).parent().parent().find(".clonedOption").length;var n=j+1;var p=f(this).closest("div").attr("id");var m=f(this).closest("div").children("label").attr("for");var i=m.replace(new RegExp(/(\d+)$/g),"");var l=p.replace(new RegExp(/(\d+)$/g),"");var k=f("#"+p).clone().attr("id",l+n);k.children("label").attr("for",i+n);k.find('input[type="text"]').attr("id",i+n);k.find('input[type="radio"]').attr("value",n);f("#"+l+j).after(k)});f(document).on("click","a.deleteOption",function(j){j.preventDefault();var i=f(this).parent().parent().find(".clonedOption").length;if(i-1==0){alert("You must have at least one option.")}else{f(this).closest("div").remove()}});f(document).on("click","a.addEmail",function(o){o.preventDefault();var j=f(this).closest("#email-details").find(".clonedOption").length;var n=j+1;var p=f(this).closest("div").attr("id");var m=f(this).closest("div").find("label").attr("for");var i=m.replace(new RegExp(/(\d+)$/g),"");var l=p.replace(new RegExp(/(\d+)$/g),"");var k=f("#"+p).clone().attr("id",l+n);k.find("label").attr("for",i+n);k.find("input").attr("id",i+n);f("#"+l+j).after(k)});f(document).on("click","a.deleteEmail",function(j){j.preventDefault();var i=f(this).closest("#email-details").find(".clonedOption").length;if(i-1==0){alert("You must have at least one option.")}else{f(this).closest("div").remove()}});f(".menu-delete, .entry-delete").click(function(){var i=(f(this).hasClass("entry-delete"))?"entry":"form";var j=confirm("You are about to permanently delete this "+i+" and all of its data.\n'Cancel' to stop, 'OK' to delete.");if(j){return true}return false});f(document).on("click","a.item-edit",function(i){i.preventDefault();f(i.target).closest("li").children(".menu-item-settings").slideToggle("fast");f(this).toggleClass("opened")});var h=f("#menu-to-edit .item-type:first").text();b(h);function b(i){if("FIELDSET"!==i){f("#vfb-fieldset-first-warning").show()}else{f("#vfb-fieldset-first-warning").hide()}}f("#menu-to-edit").nestedSortable({listType:"ul",maxLevels:3,handle:".menu-item-handle",placeholder:"sortable-placeholder",forcePlaceholderSize:true,forceHelperSize:true,tolerance:"pointer",toleranceElement:"> dl",items:"li:not( .ui-state-disabled )",create:function(i,j){f(this).css("min-height",f(this).height())},start:function(i,j){j.placeholder.height(j.item.height())},stop:function(j,k){var i=f("#menu-to-edit .item-type:first").text();opts={url:ajaxurl,type:"POST",async:true,cache:false,dataType:"json",data:{action:"visual_form_builder_process_sort",order:f(this).nestedSortable("toArray")},success:function(m){f("#loading-animation").hide();b(i);return}};f.ajax(opts)}});f("#form-items .vfb-draggable-form-items").click(function(i){i.preventDefault();f(this).data("submit_value",f(this).text())});f(document).on("click","#form-items .vfb-draggable-form-items",function(k){k.preventDefault();var l=f(this).closest("form").serializeArray(),j=f(this).data("submit_value"),i=f("#menu-to-edit li.ui-state-disabled:first").attr("id").match(new RegExp(/(\d+)$/g))[0];f("img.waiting").show();f.ajax({url:ajaxurl,type:"POST",async:true,cache:false,dataType:"html",data:{action:"visual_form_builder_create_field",data:l,field_type:j,previous:i,page:pagenow,nonce:f("#_wpnonce").val()},success:function(m){f("img.waiting").hide();f(m).hide().insertBefore("#menu-to-edit li.ui-state-disabled:first").fadeIn();return},error:function(n,o,m){alert(n+" "+o+" "+m);return}})});f(document).on("click","a.item-delete",function(q){q.preventDefault();var n=childs=new Array(),v=0,k=f(this).attr("href"),j=k.split("&"),p=confirm("You are about to permanently delete this field.\n'Cancel' to stop, 'OK' to delete.");if(!p){return false}for(var o=0;o<j.length;o++){var s=j[o].indexOf("=");var r=j[o].substring(0,s);var u=j[o].substring(s+1);n[r]=u}var l=f(this).closest(".form-item").find("ul").children();var m=l.parent().html();l.each(function(t){childs[t]=f(this).attr("id").match(new RegExp(/(\d+)$/g))[0]});var w=f(this).closest("li.form-item").parents("li.form-item");if(w.length){v=w.attr("id").match(new RegExp(/(\d+)$/g))[0]}f.ajax({url:ajaxurl,type:"POST",async:true,cache:false,dataType:"html",data:{action:"visual_form_builder_delete_field",form:n.form,field:n.field,child_ids:childs,parent_id:v,page:pagenow,nonce:n._wpnonce},success:function(i){f("#form_item_"+n.field).addClass("deleting").animate({opacity:0,height:0},350,function(){f(this).before(m).remove()});return},error:function(t,x,i){alert("There was an error loading the content");return}})});f("#form-settings-button").click(function(k){k.preventDefault();f(this).toggleClass("current");f("#form-settings").slideToggle();var i=f('input[name="form_id"]').val(),j=(f(this).hasClass("current"))?"opened":"closed";f.ajax({url:ajaxurl,type:"POST",async:true,cache:false,data:{action:"visual_form_builder_form_settings",form:i,status:j,page:pagenow},success:function(l){if(j=="closed"){f(".settings-links").removeClass("on");f(".settings-links:first").addClass("on");f(".form-details").slideUp("normal");f(".form-details:first").show("normal")}},error:function(m,n,l){alert("There was an error loading the content");return}})});f(".settings-links").click(function(k){k.preventDefault();f(".settings-links").removeClass("on");f(".form-details").slideUp("normal");if(f(this).next("div").is(":hidden")==true){f(this).addClass("on");f(this).next().slideDown("normal")}var j=f('input[name="form_id"]').val(),i=this.hash.replace(/#/g,"");f.ajax({url:ajaxurl,type:"POST",async:true,cache:false,data:{action:"visual_form_builder_form_settings",form:j,accordion:i,page:pagenow},success:function(l){},error:function(m,n,l){alert("There was an error loading the content");return}})});if(f(".columns-2 #side-sortables").length>0){var e=f("#vfb_form_items_meta_box"),g=e.offset(),c=55;f(window).on("scroll",function(){if(f(window).scrollTop()>g.top){e.stop().css({marginTop:f(window).scrollTop()-g.top+c})}else{e.stop().css({marginTop:0})}})}var d=f(".form-success-type:checked").val();f("#form-success-message-"+d).show();f(".form-success-type").change(function(){var i=f(this).val();if("text"==i){f("#form-success-message-text").show();f("#form-success-message-page, #form-success-message-redirect").hide()}else{if("page"==i){f("#form-success-message-page").show();f("#form-success-message-text, #form-success-message-redirect").hide()}else{if("redirect"==i){f("#form-success-message-redirect").show();f("#form-success-message-text, #form-success-message-page").hide()}}}});f(".vfb-field-types").click(function(j){j.preventDefault();f("#vfb-field-tabs li").removeClass("tabs");f(this).parent().addClass("tabs");f(".tabs-panel-active").removeClass("tabs-panel-active").addClass("tabs-panel-inactive");var i=this.hash;f(i).removeClass("tabs-panel-inactive").addClass("tabs-panel-active")});f("#visual-form-builder-update").validate({rules:{"form_email_to[]":{email:true},form_email_from:{email:true},form_success_message_redirect:{url:true},form_notification_email_name:{required:function(i){return f("#form-notification-setting").is(":checked")}},form_notification_email_from:{required:function(i){return f("#form-notification-setting").is(":checked")},email:true},form_notification_email:{required:function(i){return f("#form-notification-setting").is(":checked")}}},errorPlacement:function(i,j){i.insertAfter(j.parent())}});f("#visual-form-builder-new-form").validate();f("#vfb-export-select-all").click(function(i){i.preventDefault();f('#vfb-export-entries-fields input[type="checkbox"]').prop("checked",true)});f(document).on("change","#vfb-export-entries-forms",function(){var i=f(this).prop("value");f.ajax({url:ajaxurl,type:"POST",async:true,cache:false,dataType:"html",data:{action:"vfb_display_entries_load_options",id:i,page:pagenow},success:function(j){f("#vfb-export-entries-fields").html(j)},error:function(k,l,j){alert(k+" "+l+" "+j);return}})});f("#form_email_from_name_override").change(function(){if(f("#form_email_from_name_override").val()==""){f("#form-email-sender-name").attr("readonly",false)}else{f("#form-email-sender-name").attr("readonly","readonly")}});f("#form_email_from_override").change(function(){if(f("#form_email_from_override").val()==""){f("#form-email-sender").attr("readonly",false)}else{f("#form-email-sender").attr("readonly","readonly")}});if(f("#form-notification-setting").is(":checked")){f("#notification-email").show()}else{f("#notification-email").hide()}f("#form-notification-setting").change(function(){var i=f(this).is(":checked");if(i){f("#notification-email").show();f("#form-notification-email-name, #form-notification-email-from, #form-notification-email, #form-notification-subject, #form-notification-message, #form-notification-entry").attr("disabled",false)}else{f("#notification-email").hide();f("#form-notification-email-name, #form-notification-email-from, #form-notification-email, #form-notification-subject, #form-notification-message, #form-notification-entry").attr("disabled","disabled")}})});