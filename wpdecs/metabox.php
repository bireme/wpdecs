<?php $wpdecs_terms = get_post_meta($post_id, 'wpdecs_terms', true); ?>
<?php //var_dump($wpdecs_terms); ?>

<script>
	var webservice_url = "<?php print WPDECS_URL; ?>/webservice.php";
	
	$ = jQuery;
	$(function(){

		$('#wpdecs_word').keypress(function (e) {
			if (e.which == 13) {
				e.preventDefault();
				$("#wpdecs_submit").trigger('click');
			}
		});

		// submit form
		$("#wpdecs_submit").click(function(e){
			e.preventDefault();

			var lang = $("#wpdecs_lang").val();
			var word = $("#wpdecs_word").val();
			
			if(word != "") {

				// ajax request
				$.get(webservice_url, {lang: lang, words: word}, function( data ){
					
					// limpando a tabela
					$("#search_results tbody").empty();
					$("#search_results tbody").hide();

					var link_externo = "http://decs.bvs.br/cgi-bin/wxis1660.exe/?IsisScript=../cgi-bin/decsserver/decsserver.xis&path_database=/home/decs2014/www/bases/&path_cgibin=/home/decs2014/www/cgi-bin/decsserver/&path_data=/decsserver/&temp_dir=/tmp&debug=&clock=&client=&search_language=p&interface_language=p&navigation_bar=Y&format=LONG&show_tree_number=F&list_size=200&from=1&count=5&total=7&no_frame=T&task=hierarchic&previous_task=list_terms&previous_page=list_terms&mfn_tree=";
					
					var count=0;
					for(item in data.descriptors) {

						$("#result_example_title").empty();
						$("#result_example_definition").empty();
						
						var content = data.descriptors[item];

						$("#result_example .select_term").attr('onclick', "javascript: select_term('"+content.tree_id+"', '"+item+"');");
						$("#result_example_title").html(item);

						if(content.synonym == false) {
							$("#result_example_definition").html(content.definition);

						} else {
							var synonym = ' <small><span title="<?php _e("Synonym", "wpdecs"); ?>">(s)</span></small> ';
							$("#result_example_title").html('<span class="synonym">'+$("#result_example_title").html()+synonym+'</span>');
						}

						// mfn needs to be six digits to construct external link
						var mfn = content.mfn.toString();
						while(mfn.length != 6) {
							mfn = "0" + mfn;
						}						

						// external link
						$("#result_example_link a").attr('href', link_externo+mfn);
						
						// qualifiers
						$("#see_qualifiers").attr('onclick', 'javascript: show_qualifiers("ql_'+count+'");');
						var ql = "<ul>";
						for(qualifier in content.qualifiers) {
							ql += '<li class="qualifier"><input type="checkbox" data-term-id="'+content.tree_id+"|"+item+'" value="'+qualifier+'"> ' + content.qualifiers[qualifier] + '</li>';
						}
						ql += "</ul>";

						var ql_html = "<tr id='ql_"+count+"' style='display:none'><td class='qualifiers' colspan='5'>"+ql+"</td></tr>";
						// append result
						$("#search_results").append("<tr class='row-result' data-id='"+content.tree_id+"|"+item+"'>"+$("#result_example").html()+"</tr>"+ql_html);

						count += 1;
					}

					if(data.descriptors.length < 1) {
						$("#search_results").append("<tr class='row-result'><td colspan=5><i><?php _e('No results', 'wpdecs'); ?></i></td></tr>");
					}

					// efeito no form
					$("#search_results tbody").fadeIn('fast');
				});
			}
		});
		
		$('.show_ql').click(function(e){
			e.preventDefault();
			alert();
		});
	});
		
	var total_selected = <?php print count($wpdecs_terms); ?>;

	// botao de selecionar termo
	function select_term(id, term) {
	
		id_composto = id +"|"+term;

		// if exist, remove and replace
		$("input[type=hidden]").each(function(){
			var e = $(this);
			if(e.attr('name')) {
				if(e.attr('name').indexOf(id_composto) > -1) {
					e.parent('span').empty();
				}
			}
		});

		var el = '<span>';

		// list qualifiers
		qualifiers_print = [];
		var qualifiers = $('input[data-term-id="'+id_composto+'"]:checked');
		qualifiers.each(function(){
			
			el += '<input type="hidden" name="wpdecs_terms['+id_composto+'][qualifier][]" value="'+$(this).val()+'">';

			// push qualifier in list that be printed
			qualifiers_print.push($(this).val());
		});
		
		if(qualifiers_print.length < 1) {
			qualifiers_print = "";
		} else {
			qualifiers_print = "("+qualifiers_print.join('/')+")";
		}

		// starting item
		el += '<a id="wpdecs_selected_'+total_selected+'" class="ntdelbutton" onclick="javascript: remove_selected(\'wpdecs_selected_'+total_selected+'\');">x</a> '+term + " " + qualifiers_print;
		
		// lang
		$.ajax({
			url: webservice_url,
			async : false,
			context: document.body,
		    data: { 
		        treeid: id
		    },
		    cache: false,
		    type: "GET",
		    success: function(data) {
				for(l in data) {
					el += '<input type="hidden" name="wpdecs_terms['+id_composto+'][lang]['+l+']" value="'+data[l]+'">';
				}
		    }
		});

		el += '<input type="hidden" name="wpdecs_terms['+id_composto+'][term]" value="'+term+'">';
		el += '</span>';
		
		$("#selected_terms").append(el);
		total_selected += 1;
	}

	// remover termo selecionado
	function remove_selected(id) {
		$("#"+id).parent('span').empty();
	}

	function show_qualifiers(id) {
		$("#"+id).toggle();
	}

</script>
<style>
	/* hiding decs term box */
	label[for="decs_id-hide"], #decsdiv {
		display: none;
	}
	.words table {
		margin: 30px 0;
		width: 100%;
		border: 1px solid #dfdfdf;
		border-spacing: 0;
		background-color: #f9f9f9;
	}
	.words table thead th {
		padding: 5px 8px 8px;
		background-color: #f1f1f1;
	}
	.words table tr{
		line-height: 200%;
	}
	.words table tr:hover {
		background-color: #f0f0f0;
	}
	.words table .row-result:nth-child(2n){
		background-color: #f3f3f3;
	}
	.words table tbody tr td {
		padding: 2px;
	}
	li.qualifier {
		float:left;
  		display:inline;
  		width: 20%;
  		text-align: left;
  		margin: 0 10px;
	}
	.words table .definition {
		font-size: 8pt;
		color: #333;
	}
	.words table .synonym {
		margin-left: 15px;
		font-size: 9pt;
		color: #aaa;
	}
</style>

<div class="wrap">
	<p><?php _e('Search and select words.', 'wpdecs'); ?></p>
	
	<?php $wpdecs_default_language = get_option('wpdecs_default_language'); ?>
	<input type="text" name="word" id="wpdecs_word" class="code" size="40" value="dengue">
	<select name="lang" id="wpdecs_lang" class="wpdecs_lang">
		<option <?php if($wpdecs_default_language == "p") print "selected='selected'"; ?> value="p"> <?php _e('Portuguese', 'wpdecs'); ?></option>
        <option <?php if($wpdecs_default_language == "i") print "selected='selected'"; ?> value="i"> <?php _e('English', 'wpdecs'); ?></option>
        <option <?php if($wpdecs_default_language == "e") print "selected='selected'"; ?> value="e"> <?php _e('Spanish', 'wpdecs'); ?></option>
	</select>
	<input type="button" class="button" id="wpdecs_submit" value="<?php _e('Search', 'wpdecs'); ?>">
	

	<div class="words">
		
		<table id="search_selecteds">
			<thead>
				<tr>
					<th><?php _e('Selected Terms', 'wpdecs'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><div class="tagchecklist" id="selected_terms">
						<?php $count = 0; foreach($wpdecs_terms as $id => $term): ?>
							<span>
								
								<a id="wpdecs_selected_<?= $count ?>" class="ntdelbutton" onclick="javascript: remove_selected('wpdecs_selected_<?= $count ?>');">x</a> <?= $term['term'] ?> 
								<?php if(isset($term['qualifier'])) {
									// printing qualifiers, if exist
									print "(" . join("/", $term['qualifier']) . ")";
								}?>
								
								<input type="hidden" name="wpdecs_terms[<?= $id ?>][term]" value="<?= $term['term'] ?>">
								
								<!-- qualifiers -->
								<?php foreach($term['qualifier'] as $ql) : ?>
									<input type="hidden" name="wpdecs_terms[<?= $id ?>][qualifier][]" value="<?= $ql ?>">
								<?php endforeach; ?>

								<!-- langs -->
								<?php foreach($term['lang'] as $key => $value) : ?>
									<input type="hidden" name="wpdecs_terms[<?= $id ?>][lang][<?= $key ?>]" value="<?= $value ?>">
								<?php endforeach; ?>
								

							</span>
						<?php $count++; endforeach; ?>


					</div></td>
				</tr>
			</tbody>
		</table>
		
		<table id="search_results">
			<thead>
				<tr>
					<th width="1%"></th>
					<th width="1%"></th>
					<th width="25%"><?php _e('Term', 'wpdecs'); ?></th>				
					<th width="60%"><?php _e('Description', 'wpdecs'); ?></th>
					<th width="10%"><?php _e('Link', 'wpdecs'); ?></th>
				</tr>
				
				<tr style="display:none" id="result_example">
					
					<td><input type="button" class="select_term" value="+" onclick="javascript: select_term(this);"></td>
					<td><input type="button" id="see_qualifiers" value="Q"></td>
					<td id="result_example_title"></td>
					
					<td id="result_example_definition" class='definition'></td>
					<td id="result_example_link">
						<a href="javascript:void(0);" target="_blank" title="<?php _e("External Link", "wpdecs"); ?>"><?php _e("External Link", "wpdecs"); ?></a>
					</td>
				</tr>
				
			</thead>
			<tbody>
				<tr><td colspan="5"><i><?php _e("No results"); ?></i></td></tr>
			</tbody>
		</table>
		
	</div>
</div>