<?php

function wordWrapAnnotation(&$image, &$draw, $text, $maxWidth)
{
   $regex = '/( |(?=\p{Han})(?<!\p{Pi})(?<!\p{Ps})|(?=\p{Pi})|(?=\p{Ps}))/u';
   $cleanText = trim(preg_replace('/[\s\v]+/', ' ', $text));
   $strArr = preg_split($regex, $cleanText, -1, PREG_SPLIT_DELIM_CAPTURE |
                                                PREG_SPLIT_NO_EMPTY);
   $linesArr = array();
   $lineHeight = 0;
   $goodLine = '';
   $spacePending = false;
   
   foreach ($strArr as $str) {
      if ($str == ' ') {
         $spacePending = true;
      } else {
         if ($spacePending) {
            $spacePending = false;
            $line = $goodLine.' '.$str;
         } else {
            $line = $goodLine.$str;
         }
         $metrics = $image->queryFontMetrics($draw, $line);
         if ($metrics['textWidth'] > $maxWidth) {
            if ($goodLine != '') {
               $linesArr[] = $goodLine;
            }
            $goodLine = $str;
         } else {
            $goodLine = $line;
         }
         if ($metrics['textHeight'] > $lineHeight) {
            $lineHeight = $metrics['textHeight'];
         }
      }
   }
   if ($goodLine != '') {
      $linesArr[] = $goodLine;
   }

   return [$linesArr, $lineHeight];
}

function createComp($comp)
{
	$image = new Imagick();
	$draw = new ImagickDraw();
	$bgpixel = new ImagickPixel("black");
	$height = 260;
	
	if($comp["desc-enabled"]){
		$description = $comp["description"];
		
		$draw->setFontSize(30);
		list($lines, $lineHeight) = wordWrapAnnotation($image, $draw, $description, 850);
		$height+= $lineHeight * count($lines);
	}
	
	$image->newImage(900, $height, $bgpixel);
	$image->setImageFormat("png");
	$draw->setFont("./assets/bignoodletoo.ttf");
	$draw->setFontStyle(Imagick::STYLE_ITALIC);
	$draw->setFontSize(80);
	$draw->setFillColor("white");
	$image->annotateImage($draw, 20, 80, 0, $comp["name"]);
	$x = 20;
	$y = 100;
	foreach($comp["comp"] as $hero)
	{
		$newheroes = loadHeroes();
		if (gettype($hero) == "array")
		{
			$hero = array_reverse($hero);
			switch (count($hero))
			{
			case 2:
				$i = 0;
				foreach($hero as $subhero)
				{
					$i++;
					$newheroes[$subhero]->resizeImage(90, 90, Imagick::FILTER_LANCZOS, 1);
					if ($i == 1)
					{
						$image->compositeImage($newheroes[$subhero], Imagick::COMPOSITE_DEFAULT, $x + 45, $y + 40);
					}
					else
					{
						$image->compositeImage($newheroes[$subhero], Imagick::COMPOSITE_DEFAULT, $x + 5, $y);
					}
				}

				break;

			case 3:
				$i = 0;
				foreach($hero as $subhero)
				{
					$i++;
					$newheroes[$subhero]->resizeImage(90, 90, Imagick::FILTER_LANCZOS, 1);
					if ($i == 1)
					{
						$image->compositeImage($newheroes[$subhero], Imagick::COMPOSITE_DEFAULT, $x + 50, $y + 40);
					}
					else
					if ($i == 2)
					{
						$image->compositeImage($newheroes[$subhero], Imagick::COMPOSITE_DEFAULT, $x, $y + 40);
					}
					else
					{
						$image->compositeImage($newheroes[$subhero], Imagick::COMPOSITE_DEFAULT, $x + 25, $y - 5);
					}
				}

				break;

			case 4:
				$i = 0;
				foreach($hero as $subhero)
				{
					$i++;
					$newheroes[$subhero]->resizeImage(80, 80, Imagick::FILTER_LANCZOS, 1);
					if ($i == 1)
					{
						$image->compositeImage($newheroes[$subhero], Imagick::COMPOSITE_DEFAULT, $x + 35, $y + 50);
					}
					else
					if ($i == 2)
					{
						$image->compositeImage($newheroes[$subhero], Imagick::COMPOSITE_DEFAULT, $x - 10, $y + 50);
					}
					else
					if ($i == 3)
					{
						$image->compositeImage($newheroes[$subhero], Imagick::COMPOSITE_DEFAULT, $x + 15, $y + 10);
					}
					else
					{
						$image->compositeImage($newheroes[$subhero], Imagick::COMPOSITE_DEFAULT, $x + 65, $y + 10);
					}
				}

				break;
			}
		}
		else
		{
			$image->compositeImage($newheroes[$hero], Imagick::COMPOSITE_DEFAULT, $x, $y);
		}

		$x+= 135.5;
	}

	$x = 20;
	$y = 260;
	$draw->setFontSize(30);

	if($comp["desc-enabled"]){
		for ($k = 0; $k < count($lines); $k++)
		{
			$image->annotateImage($draw, $x, $y + $k * $lineHeight, 0, $lines[$k]);
		}
	}
	return [$image, $height];
}

function loadHeroes()
{

	// Load heroes

	$heroes = array();
	foreach(array_diff(scandir("./assets/heroes") , array(
		'..',
		'.'
	)) as $file)
	{
		$name = str_replace(".png", "", $file);
		$heroes[$name] = new Imagick();
		$heroes[$name]->readImage("./assets/heroes/{$file}");
		$heroes[$name]->resizeImage($heroes[$name]->getImageWidth() / 2, $heroes[$name]->getImageWidth() / 2, Imagick::FILTER_LANCZOS, 1);
	}

	return $heroes;
}

function processPost()
{
	$allComps = array();
	$compAmount = sizeof($_POST) / 8;
	for ($i = 1; $i <= $compAmount; $i++)
	{
		$comp = array();
		for ($j = 1; $j <= 6; $j++)
		{
			if (sizeof($_POST["comp{$i}-hero{$j}"]) == 1)
			{
				array_push($comp, $_POST["comp{$i}-hero{$j}"][0]);
			}
			else
			{
				$multihero = array();
				foreach($_POST["comp{$i}-hero{$j}"] as $hero)
				{
					array_push($multihero, $hero);
				}

				array_push($comp, $multihero);
			}
		}

		$finalComp = array(
			"name" => $_POST["comp{$i}-name"],
			"comp" => $comp,
			"desc-enabled" => isset($_POST["comp{$i}-desc-enabled"]),
			"description" => $_POST["comp{$i}-desc"]
		);
		array_push($allComps, $finalComp);
	}

	return $allComps;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$comps = processPost();
	$image = new Imagick();
	$draw = new ImagickDraw();
	$bgpixel = new ImagickPixel("black");
	$width = 900;
	$height = 150;
	$imagesToDraw = [];
	foreach($comps as $comp)
	{
		$comp = createComp($comp);
		$height+= $comp[1];
		array_push($imagesToDraw, $comp);
	}

	$image->newImage($width, $height, $bgpixel);
	$image->setImageFormat("png");
	$draw->setFillColor("white");
	$draw->setFont("./assets/bignoodletoo.ttf");
	$draw->setFontStyle(Imagick::STYLE_ITALIC);
	$draw->setFontSize(100);
	$image->annotateImage($draw, 20, 100, 0, "Composition Cheat Sheet");
	$draw->setFontSize(80);
	$y = 150;
	
	foreach($imagesToDraw as $compImage)
	{
		$image->compositeImage($compImage[0], Imagick::COMPOSITE_DEFAULT, 0, $y);
		$y+= $compImage[1];
	}
	
	header("Content-Type: image/png");
	echo $image;
}
else
{
?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">-->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.1.3/slate/bootstrap.min.css">

    <title>Composition Cheat Sheet Builder</title>
  </head>
  <body>
  <form action="./index.php" method="post" target="_blank">
		<div class="container">
			<div class="container-fluid">
				<br />
				<br />
				<h1>Composition Cheat Sheet Builder</h1>
				<div class="row">
					<br />
				</div>
				<div id="row-anchor"></div>
				<div class="row">
					<div class='col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2 mt-4'>
						<div class="btn-group-vertical">
							<a href='#' id='addComp' onclick='addComp(); return false' class='nav-link'>Add composition</a>
							<input type="submit" value="Generate" class="btn btn-primary btn-sm">
						</div>
					</div>
				</div>
				<div class="row">
					<br />
				</div>
			</div>
		</div>
				
		<template id="comp-template">
			<div class="row">
				<div class='col-md-auto'>
					<div class="btn-group-vertical">
						<label for="comp[i]-name">Composition name</label>
						<input type="text" class="form-control-sm" id="comp[i]-name" name="comp[i]-name" value="Composition [i]">
					</div>
				</div>
			</div>
			<div id="comp[i]" class="row">
				<div id="comp[i]-hero1">
				</div>
				<div id="comp[i]-hero2">
				</div>
				<div id="comp[i]-hero3">
				</div>
				<div id="comp[i]-hero4">
				</div>
				<div id="comp[i]-hero5">
				</div>
				<div id="comp[i]-hero6">
				</div>
			</div>
			<div class="row">
				<div class="col-6 col-md-6 mt-4">
					<label for="comp[i]-desc"><input type="checkbox" class="form-check-input" id="comp[i]-desc-enabled" name="comp[i]-desc-enabled" onchange="toggleDesc([i]); return false"> Description</label>
					<textarea type="text" class="form-control" id="comp[i]-desc" name="comp[i]-desc" rows="3" disabled></textarea>
				</div>
			</div>
			<div class="row">
				<br />
			</div>
		</template>
		
		<template id="comp-column-template">
			<div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2 mt-4">
				<div class="card">
					<div id="comp[i]-img[j]-anchor">
						<img src="./assets/heroes/Ana.png" id="comp[i]-img[j]" width="100%">
					</div>
					<a href="#" id="comp[i]-addHero[j]" onclick="addHero([i], [j]); return false" class="nav-link">+ Add</a>
					<div id="hero-select"></div>
				</div>
			</div>
		</template>
		
		<template id="single-hero-img-template">
			<img src="./assets/heroes/[hero1].png" id="comp[i]-img[j]" width="100%">
		</template>
		
		<template id="double-hero-img-template">
			<img src="./assets/heroes/[hero1].png" id="comp[i]-img[j]" width="50%">
			<div align="right">
				<img src="./assets/heroes/[hero2].png" id="comp[i]-img[j]" width="50%" align="right">
			</div>
		</template>
		
		<template id="triple-hero-img-template">
			<div align="center">
				<img src="./assets/heroes/[hero1].png" id="comp[i]-img[j]" width="50%">
			</div>
			<img src="./assets/heroes/[hero2].png" id="comp[i]-img[j]" width="50%">
			<img src="./assets/heroes/[hero3].png" id="comp[i]-img[j]" width="50%" align="right">
		</template>
		
		<template id="quadra-hero-img-template">
			<img src="./assets/heroes/[hero2].png" id="comp[i]-img[j]" width="50%">
			<img src="./assets/heroes/[hero1].png" id="comp[i]-img[j]" width="50%" align="right">
			<img src="./assets/heroes/[hero3].png" id="comp[i]-img[j]" width="50%">
			<img src="./assets/heroes/[hero4].png" id="comp[i]-img[j]" width="50%" align="right">
		</template>
		
		<template id="add-hero-template">
			<a href="#" id="comp[i]-addHero[j]" onclick="addHero([i], [j]); return false" class="nav-link">+ Add</a>
		</template>
		
		<template id="remove-hero-template">
			<a href="#" id="comp[i]-removeHero[j]" onclick="removeHero([i], [j]); return false" class="nav-link">- Remove</a>
		</template>

		<template id="hero-select-template">
			<select name="comp[i]-hero[j][]" id="comp[i]-hero[j]" onchange="showHero([i], [j]); return false" class="form-control-sm">
				<option value="No Hero">No Hero</option>
				<option value="Ana" selected>Ana</option>
				<option value="Ashe">Ashe</option>
				<option value="Bastion">Bastion</option>
				<option value="Brigitte">Brigitte</option>
				<option value="DVa">DVa</option>
				<option value="Doomfist">Doomfist</option>
				<option value="Genji">Genji</option>
				<option value="Hanzo">Hanzo</option>
				<option value="Junkrat">Junkrat</option>
				<option value="Lucio">Lucio</option>
				<option value="McCree">McCree</option>
				<option value="Mei">Mei</option>
				<option value="Mercy">Mercy</option>
				<option value="Moira">Moira</option>
				<option value="Orisa">Orisa</option>
				<option value="Pharah">Pharah</option>
				<option value="Reaper">Reaper</option>
				<option value="Reinhardt">Reinhardt</option>
				<option value="Roadhog">Roadhog</option>
				<option value="Soldier 76">Soldier 76</option>
				<option value="Sombra">Sombra</option>
				<option value="Symmetra">Symmetra</option>
				<option value="Torbjorn">Torbjorn</option>
				<option value="Tracer">Tracer</option>
				<option value="Widowmaker">Widowmaker</option>
				<option value="Winston">Winston</option>
				<option value="Wrecking Ball">Wrecking Ball</option>
				<option value="Zarya">Zarya</option>
				<option value="Zenyatta">Zenyatta</option>
			</select>
		</template>
		
		<template id="premade-comp-select-template">
			<div class="row">
				<div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2 mt-4 btn-group" role="group">
					<button type="button" class="btn btn-primary btn-sm" onclick="importComp([i]); return false">Import Comp</button>
					<button type="button" class="btn btn-primary btn-sm" onclick="saveCompToFile([i]); return false">Export Comp</button>
					&nbsp;
					<select name="comp[i]-premade" id="comp[i]-premade" onchange="loadComp([i]); return false" class="form-control-sm">
					</select>
					<input type="file" accept="application/json" name="comp[i]-file" id="comp[i]-file" style="visibility: hidden;" onchange="loadCompFromCustomFile([i]); return false">
				</div>
			</div>
		</template>
		
		<template id="premade-comp-select-options-template">
			<option value="[comp-name]">[comp-name]</option>
		</template>
		
		<!-- jQuery first, then Popper.js, then Bootstrap JS -->
		<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
	
		
		<!-- Optional JavaScript -->
		<script>
			function showHero(i, j) {
				let heroAmount = $("select#comp" + i + "-hero" + j).length;
				$("#comp" + i + "-img" + j + "-anchor").html("");
				switch (heroAmount){
					case 1:
						$("#comp" + i + "-img" + j + "-anchor").html($("#single-hero-img-template").html().replace(/\[i\]/g, i).replace(/\[j\]/g, j));
						break;
					case 2:
						$("#comp" + i + "-img" + j + "-anchor").html($("#double-hero-img-template").html().replace(/\[i\]/g, i).replace(/\[j\]/g, j));
						break;
					case 3:
						$("#comp" + i + "-img" + j + "-anchor").html($("#triple-hero-img-template").html().replace(/\[i\]/g, i).replace(/\[j\]/g, j));
						break;
					case 4:
						$("#comp" + i + "-img" + j + "-anchor").html($("#quadra-hero-img-template").html().replace(/\[i\]/g, i).replace(/\[j\]/g, j));
						break;
					default:
						
				}
				
				let k = 0;
				$("select#comp" + i + "-hero" + j + " option:selected").each(function() {
					let hero = $(this).val();
					switch(k){
						case 0:
							$("#comp" + i + "-img" + j + "-anchor").html($("#comp" + i + "-img" + j + "-anchor").html().replace(/\[hero1\]/g, hero));
							break;
						case 1:
							$("#comp" + i + "-img" + j + "-anchor").html($("#comp" + i + "-img" + j + "-anchor").html().replace(/\[hero2\]/g, hero));
							break;
						case 2:
							$("#comp" + i + "-img" + j + "-anchor").html($("#comp" + i + "-img" + j + "-anchor").html().replace(/\[hero3\]/g, hero));
							break;
						case 3:
							$("#comp" + i + "-img" + j + "-anchor").html($("#comp" + i + "-img" + j + "-anchor").html().replace(/\[hero4\]/g, hero));
							break;
						default:
						}
						k++;
					});
					
					/*
					let hero = $("#comp" + i + "-hero" + j).children("option:selected").val();
					$("#comp" + i + "-img" + j + "-anchor").html().replace(/\[hero1\]/g, i);
					$("#comp" + i + "-img" + j).attr("src", "./assets/heroes/" + hero + ".png");
					*/
				}
			
			function addHero(i, j) {
				if(compHeroes[i][j - 1] == 1){
					$("#comp" + i + "-addHero" + j).after($("#hero-select-template").html().replace(/\[i\]/g, i).replace(/\[j\]/g, j));
				}else{
					$("#comp" + i + "-removeHero" + j).after($("#hero-select-template").html().replace(/\[i\]/g, i).replace(/\[j\]/g, j));
				}
				compHeroes[i][j - 1]++;
				
				if(compHeroes[i][j - 1] == 1){
					$("#comp" + i + "-removeHero" + j).remove();
				}
				
				if(compHeroes[i][j - 1] == 2){
					$("#comp" + i + "-addHero" + j).after($("#remove-hero-template").html().replace(/\[i\]/g, i).replace(/\[j\]/g, j));
				}
				
				if(compHeroes[i][j - 1] == 4){
					$("#comp" + i + "-addHero" + j).remove();
				}
				showHero(i, j);
			}
			
			function removeHero(i, j) {
				$("select#comp" + i + "-hero" + j).last().remove();
				compHeroes[i][j - 1]--;
				
				if(compHeroes[i][j - 1] == 1){
					$("#comp" + i + "-removeHero" + j).remove();
				}
				
				if(compHeroes[i][j - 1] == 3){
					$("#comp" + i + "-removeHero" + j).before($("#add-hero-template").html().replace(/\[i\]/g, i).replace(/\[j\]/g, j));
				}
				showHero(i, j);
			}
			
			function addComp() {
				compAmount++;				
				$("#row-anchor").before($("#comp-template").html().replace(/\[i\]/g, compAmount));
				
				let j = 0;
				$("#comp" + compAmount).find("div").each(function () {
					j++;
					$(this).replaceWith($("#comp-column-template").html().replace(/\[i\]/g, compAmount).replace(/\[j\]/g, j));
				});
				
				j = 0;
				$("#comp" + compAmount).find("div #hero-select").each(function () {
					j++;
					$(this).replaceWith($("#hero-select-template").html().replace(/\[i\]/g, compAmount).replace(/\[j\]/g, j));
				});
				compHeroes[compAmount] = [1, 1, 1, 1, 1, 1];
				if(compAmount > 1){
					location.href = "#comp" + compAmount;
				}
				loadCompSelectionBox(compAmount);
			}
			
			function loadComp(i) {
				let name = $("#comp" + i + "-premade").children("option:selected").val();
				if(name != "Load premade comp"){
					loadCompFromFile(i, name);					
				}
			}
			
			function importComp(i) {
				$("#comp" + i + "-file").click();
			}
			
			function loadCompFromCustomFile(i) {
				let reader = new FileReader();
				reader.onload = function(e){
					let json = JSON.parse(reader.result);
					processCompFile(i, json);
				}
				reader.readAsText($("#comp1-file")[0].files[0]);
			}
			
			function loadCompFromFile(i, name) {
				let file = "./comps/" + name + ".json";

				$.getJSON(file, function(json) {
					processCompFile(i, json);
				});
			}
			
			function processCompFile(i, json) {
				$("#comp" + i + "-name").attr("value", json.name);
				$("#comp" + i + "-desc").html(json.description);
				let j = 1;
				$.each(json.comp, function(key, value){	
					let heroAmount = 1;
					if($.isArray(value)){
						heroAmount = value.length;
					}
					let difference = $("select#comp" + i + "-hero" + j).length - heroAmount;
					console.log(difference);
					if(difference < 0){
						for(k = 0; k < Math.abs(difference); k++){
							addHero(i, j);
						}
					}else if (difference > 0){
						for(k = 0; k < difference; k++){
							removeHero(i, j);
						}
					}
					if($.isArray(value)){
						for(k = 0; k < heroAmount; k++){
							console.log(value[k]);
							$("select#comp" + i + "-hero" + j).eq(k).val(value[k]);							
						}
					}else{
						if($("select#comp" + i + "-hero" + j).length > 1){
							for(k = 0; k <= $("select#comp" + i + "-hero" + j).length; k++){
								removeHero(i, j);
							}
						}
						$("select#comp" + i + "-hero" + j).eq(0).val(value);
					}
					showHero(i, j);
					j++;
				});
			}
			
			function saveCompToFile(i) {
				let finalResult = {};
				finalResult["name"] = $("#comp" + i + "-name").val();
				
				let comp = [];
				
				for(j = 1; j <= 6; j++){
					if($("select#comp" + i + "-hero" + j).length > 1){
						let subHeroes = [];
						for(k = 0; k < $("select#comp" + i + "-hero" + j).length; k++){
							subHeroes.push($("select#comp" + i + "-hero" + j).eq(k).val());
						}
						comp.push(subHeroes);
					}else{
						comp.push($("select#comp" + i + "-hero" + j).eq(0).val());
					}
				}
				finalResult["comp"] = comp;
				finalResult["description"] = $("#comp" + i + "-desc").val();
				
				let dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(JSON.stringify(finalResult, null, 4));
				let linkElement = document.createElement('a');
				linkElement.setAttribute('href', dataUri);
				linkElement.setAttribute('download', $("#comp" + i + "-name").val() + ".json");
				linkElement.click();
			}
			
			function loadCompSelectionBox(i) {
				$("#comp" + i).before($("#premade-comp-select-template").html().replace(/\[i\]/g, i));
				$("#comp" + i + "-premade").html($("#premade-comp-select-options-template").html().replace(/\[comp-name\]/g, "Load premade comp"));
				$.getJSON("./comps/getcomps.php", function(json) {
					console.log(json);
					$.each(json, function(key, value){
						console.log(key);
						console.log(value);
						$("#comp" + i + "-premade").find("option").last().after($("#premade-comp-select-options-template").html().replace(/\[comp-name\]/g, value));
					});
				});
			}
			
			function toggleDesc(i) {
				$("#comp" + i + "-desc").attr("disabled", !$("#comp" + i + "-desc").attr("disabled"));
			}
			
			var compAmount = 0;
			var compHeroes = [];
			
			$.ajaxSetup({'cache': false});
			
			addComp();
		</script>
	</form>
  </body>
</html>

<?php
}

?>