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
                $line = $goodLine . ' ' . $str;
            } else {
                $line = $goodLine . $str;
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
    try {
        $image = new Imagick();
    } catch (ImagickException $e) {
    }
    $draw = new ImagickDraw();
    $bgpixel = new ImagickPixel("black");
    $heroHeight = 140;
    $nameMapFontHeight = 80;
    $nameHeight = 22;
    $spacer = 10;
    $height = $heroHeight + $spacer; // Hero Pictures are 271 / 2 = 135.5
    if ($comp["desc-enabled"] && !empty(trim($comp["description"]))) {
        $description = $comp["description"];
        $draw->setFontSize(30);
        list($lines, $lineHeight) = wordWrapAnnotation($image, $draw, $description, 850);
        $descHeight = $lineHeight * count($lines);
        $height += $descHeight;
    }
    if (!empty(trim($comp["name"])) || $comp["maps-enabled"]) {
        $height += $nameMapFontHeight + $spacer;
    }
    if ($comp["player-names-enabled"]) {
        $height += $nameHeight;
    }
    $image->newImage(900, $height, $bgpixel);
    $image->setImageFormat("png");
    $draw->setFont("./assets/bignoodletoo.ttf");
    $draw->setFontStyle(Imagick::STYLE_ITALIC);
    $draw->setFontSize($nameMapFontHeight);
    $draw->setFillColor("white");
    if (!empty(trim($comp["name"]))) {
        $image->annotateImage($draw, 20, 80, 0, $comp["name"]);
        //$image->annotateImage($draw, 20, 80, 0, $height);
    }
    if ($comp["maps-enabled"]) {
        $currentX = 740;
        $currentY = 10;
        $maps = loadMaps($nameMapFontHeight);
        foreach ($comp["maps"] as $map) {
            $image->compositeImage($maps[$map], Imagick::COMPOSITE_DEFAULT, $currentX, $currentY);
            $currentX -= 65;
        }
    }
    $currentY = $spacer;
    if (!empty(trim($comp["name"])) || $comp["maps-enabled"]) {
        $currentY += $nameMapFontHeight;
    }
    if ($comp["player-names-enabled"]) {
        $draw->setFontSize($nameHeight);
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);
        $currentX = (135.5 / 2) + 20;
        $currentY += $spacer * 3;
        for ($i = 0; $i <= 5; $i++) {
            $image->annotateImage($draw, $currentX, $currentY, 0, $comp["player-names"][$i]);
            $currentX += 135.5;
        }
        $currentY += $nameHeight - $spacer * 2;
        $draw->setFontSize($nameMapFontHeight);
        $draw->setTextAlignment(Imagick::ALIGN_LEFT);
    }
    $currentX = 20;
    $allHeroes = loadHeroes();
    foreach ($comp["comp"] as $hero) {
        if (gettype($hero) == "array") {
            $hero = array_reverse($hero);
            switch (count($hero)) {
                case 2:
                    {
                        $i = 0;
                        foreach ($hero as $subhero) {
                            $i++;
                            $heroImage = clone $allHeroes[$subhero];
                            $heroImage->scaleImage(90, 90);
                            if ($i == 1) {
                                $image->compositeImage($heroImage, Imagick::COMPOSITE_DEFAULT, $currentX + 45, $currentY + 40);
                            }
                            if ($i == 2) {
                                $image->compositeImage($heroImage, Imagick::COMPOSITE_DEFAULT, $currentX + 5, $currentY);
                            }
                        }
                        break;
                    }
                case 3:
                    {
                        $i = 0;
                        foreach ($hero as $subhero) {
                            $i++;
                            $heroImage = clone $allHeroes[$subhero];
                            $heroImage->scaleImage(90, 90);
                            switch ($i) {
                                case 1:
                                    $image->compositeImage($heroImage, Imagick::COMPOSITE_DEFAULT, $currentX + 50, $currentY + 40);
                                    break;
                                case 2:
                                    $image->compositeImage($heroImage, Imagick::COMPOSITE_DEFAULT, $currentX, $currentY + 40);
                                    break;
                                case 3:
                                    $image->compositeImage($heroImage, Imagick::COMPOSITE_DEFAULT, $currentX + 25, $currentY - 5);
                                    break;
                            }
                        }
                        break;
                    }
                case 4:
                    {
                        $i = 0;
                        foreach ($hero as $subhero) {
                            $i++;
                            $heroImage = clone $allHeroes[$subhero];
                            $heroImage->scaleImage(80, 80);
                            switch ($i) {
                                case 1:
                                    {
                                        $image->compositeImage($heroImage, Imagick::COMPOSITE_DEFAULT, $currentX + 35, $currentY + 50);
                                        break;
                                    }
                                case 2:
                                    {
                                        $image->compositeImage($heroImage, Imagick::COMPOSITE_DEFAULT, $currentX - 10, $currentY + 50);
                                        break;
                                    }
                                case 3:
                                    {
                                        $image->compositeImage($heroImage, Imagick::COMPOSITE_DEFAULT, $currentX + 15, $currentY + 10);
                                        break;
                                    }
                                case 4:
                                    {
                                        $image->compositeImage($heroImage, Imagick::COMPOSITE_DEFAULT, $currentX + 65, $currentY + 10);
                                        break;
                                    }
                            }
                        }
                        break;
                    }
            }
        }
        if (gettype($hero) == "string") {
            $image->compositeImage($allHeroes[$hero], Imagick::COMPOSITE_DEFAULT, $currentX, $currentY);
        }
        $currentX += 135.5;
    }
    $currentX = 20;
    $currentY += $heroHeight + $spacer * 2;
    $draw->setFontSize(30);
    if ($comp["desc-enabled"] && !empty(trim($comp["description"]))) {
        for ($k = 0; $k < count($lines); $k++) {
            $image->annotateImage($draw, $currentX, $currentY + $k * $lineHeight, 0, $lines[$k]);
        }
    }
    return [$image, $height];
}

function loadHeroes()
{
    // Load heroes
    $heroes = array();
    foreach (array_diff(scandir("./assets/heroes"), array(
        '..',
        '.'
    )) as $file) {
        $name = str_replace(".png", "", $file);
        try {
            $heroes[$name] = new Imagick();
        } catch (ImagickException $e) {
        }
        $heroes[$name]->readImage(__DIR__ . "/assets/heroes/{$file}");
        $heroes[$name]->scaleImage($heroes[$name]->getImageWidth() / 2, $heroes[$name]->getImageWidth() / 2);
    }
    return $heroes;
}

function loadMaps($imageSize)
{
    // Load maps
    $maps = array();
    foreach (array_diff(scandir("./assets/maps/icons"), array(
        '..',
        '.'
    )) as $file) {
        $name = str_replace(".png", "", $file);
        try {
            $maps[$name] = new Imagick();
        } catch (ImagickException $e) {
        }
        $maps[$name]->readImage(__DIR__ . "/assets/maps/icons/{$file}");
        $maps[$name]->scaleImage($imageSize, $imageSize);
    }
    return $maps;
}

function processPost()
{
    $allComps = array();
    $compAmount = 0;
    $safePost = filter_input_array(INPUT_POST);
    foreach ($safePost as $key => $value) {
        if (preg_match("/comp[\d]+-name/", $key)) {
            $compAmount++;
        }
    }
    for ($i = 1; $i <= $compAmount; $i++) {
        $comp = array();
        for ($j = 1; $j <= 6; $j++) {
            if (sizeof($safePost["comp{$i}-hero{$j}"]) == 1) {
                array_push($comp, $safePost["comp{$i}-hero{$j}"][0]);
            } else {
                $multihero = array();
                foreach ($safePost["comp{$i}-hero{$j}"] as $hero) {
                    array_push($multihero, $hero);
                }
                array_push($comp, $multihero);
            }
        }
        $maps = [];
        if (isset($safePost["comp{$i}-map"])) {
            $maps = $safePost["comp{$i}-map"];
        }
        $finalComp = array(
            "name" => $safePost["comp{$i}-name"],
            "comp" => $comp,
            "player-names-enabled" => isset($safePost["comp{$i}-player-enabled"]),
            "player-names" => $safePost["comp{$i}-player-names"],
            "desc-enabled" => isset($safePost["comp{$i}-desc-enabled"]),
            "description" => $safePost["comp{$i}-desc"],
            "maps-enabled" => isset($safePost["comp{$i}-map-enabled"]),
            "maps" => $maps
        );
        array_push($allComps, $finalComp);
    }
    return $allComps;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header("Content-Type: image/png");
    $comps = processPost();
    try {
        $image = new Imagick();
    } catch (ImagickException $e) {
    }
    $draw = new ImagickDraw();
    $bgpixel = new ImagickPixel("black");
    $imageWidth = 900;
    $imageHeight = 0;
    $titleHeight = 100;
    $imagesToDraw = [];
    $safePost = filter_input_array(INPUT_POST);
    foreach ($comps as $heroComp) {
        $comp = createComp($heroComp);
        $imageHeight += $comp[1];
        array_push($imagesToDraw, $comp);
    }
    if (isset($safePost["image-title-enabled"])) {
        $imageHeight += $titleHeight;
    }
    $image->newImage($imageWidth, $imageHeight, $bgpixel);
    $image->setImageFormat("png");
    $draw->setFillColor("white");
    $draw->setFont("./assets/bignoodletoo.ttf");
    $draw->setFontStyle(Imagick::STYLE_ITALIC);
    $imageY = 0;
    if (isset($safePost["image-title-enabled"])) {
        $draw->setFontSize(100);
        $image->annotateImage($draw, 20, $titleHeight, 0, $safePost["image-title"]);
        $imageY += $titleHeight;
    }
    foreach ($imagesToDraw as $compImage) {
        $image->compositeImage($compImage[0], Imagick::COMPOSITE_DEFAULT, 0, $imageY);
        $imageY += $compImage[1];
    }
    echo $image;
} else {
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.1.3/slate/bootstrap.min.css">
        <link rel="stylesheet" href="./css/component-chosen.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/image-select/2.0/ImageSelect.css">
        <title>Composition Cheat Sheet Builder</title>
    </head>
    <body>
    <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post" target="_blank">
        <div class="container">
            <div class="container-fluid">
                <br/>
                <br/>
                <h1>Composition Cheat Sheet Builder</h1>
                <div class="row">
                    <br/>
                </div>
                <div class="row">
                    <div class='col-6 col-md-6 mt-4'>
                        <label for="image-title"><input type="checkbox" class="form-check-input"
                                                        id="image-title-enabled" name="image-title-enabled"
                                                        onchange="toggleTitle(); return false" checked>Image
                            title</label>
                        <input type="text" name="image-title" id="image-title" value="Composition Cheat Sheet"
                               class="form-control">
                    </div>
                </div>
                <div class="row">
                    <br/>
                </div>
                <div class="row" id="row-anchor"></div>
                <div class="row">
                    <div class='col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2 mt-4'>
                        <div class="btn-group">
                            <a href='#' id='addComp' onclick='addComp(); return false' class='btn btn-primary btn-sm'>Add
                                composition</a>
                            <input type="submit" value="Generate image" class="btn btn-primary btn-sm">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <br/>
                </div>
            </div>
        </div>
        <template id="comp-template">
            <div class="row">
                <div class='col-md-auto'>
                    <div class="btn-group-vertical">
                        <label for="comp[i]-name">Composition name</label>
                        <input type="text" class="form-control form-control-sm" id="comp[i]-name" name="comp[i]-name"
                               value="Composition [i]">
                    </div>
                </div>
                <div class='col-md-auto'>
                    <label for="hero-player"><input type="checkbox" class="form-check-input"
                                                    id="comp[i]-player-enabled" name="comp[i]-player-enabled"
                                                    onchange="togglePlayer([i]); return false">Player names</label>
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
                    <label for="comp[i]-desc"><input type="checkbox" class="form-check-input" id="comp[i]-desc-enabled"
                                                     name="comp[i]-desc-enabled"
                                                     onchange="toggleDesc([i]); return false" checked>
                        Description</label>
                    <div class="card">
                        <textarea class="form-control" id="comp[i]-desc" name="comp[i]-desc" rows="4"></textarea>
                    </div>
                </div>
                <div class="col-2 col-md-2 mt-2" id="comp[i]-map1-anchor"><br/>
                    <label for="comp[i]-map">
                        <input type="checkbox" class="form-check-input" id="comp[i]-map-enabled"
                               name="comp[i]-map-enabled" onchange="toggleMap([i]); return false">
                        Maps
                        <a href="#" id="comp[i]-add-map" onclick="addMap([i]); return false" hidden>+ Add map</a>
                    </label><br/>
                    <div class="card">
                        <img src="./assets/maps/Blizzard World.jpg" id="comp[i]-map1-image" width="100%" class="rounded"
                             alt="">
                        <div id="comp[i]-map1-select">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <br/>
            </div>
        </template>
        <template id="add-map-template">
            <div class="col-2 col-md-2 mt-2" id="comp[i]-map[j]-anchor"><br/>
                <label for="comp[i]-map">
                    <a href="#" id="comp[i]-add-map" onclick="removeMap([i]); return false">- Remove</a>
                </label><br/>
                <div class="card">
                    <img src="./assets/maps/Blizzard World.jpg" id="comp[i]-map[j]-image" width="100%" class="rounded">
                    <div id="comp[i]-map[j]-select">
                    </div>
                </div>
            </div>
        </template>
        <template id="comp-column-template">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2 mt-4">
                <div class="card">
                    <div id="comp[i]-hero-player" style="display: none;">
                        <input type="text" id="comp[i]-player-names" name="comp[i]-player-names[]"
                               class="form-control form-control-sm" maxlength="12">
                    </div>
                    <div id="comp[i]-img[j]-anchor">
                        <img src="./assets/heroes/Ana.png" id="comp[i]-img[j]" width="100%">
                    </div>
                    <a href="#" id="comp[i]-addHero[j]" onclick="addHero([i], [j]); return false" class="nav-link">+
                        Add</a>
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
            <a href="#" id="comp[i]-removeHero[j]" onclick="removeHero([i], [j]); return false" class="nav-link">-
                Remove</a>
        </template>
        <template id="hero-select-template">
            <label for="comp[i]-hero[j]"></label><select data-placeholder="Ana" name="comp[i]-hero[j][]"
                                                         id="comp[i]-hero[j]"
                                                         onchange="showHero([i], [j]); return false"
                                                         class="form-control form-control-chosen">
                <option data-img-src="./assets/heroes/No Hero.png" value="No Hero">No Hero</option>
                <option data-img-src="./assets/heroes/Ana.png" value="Ana" selected>Ana</option>
                <option data-img-src="./assets/heroes/Ashe.png" value="Ashe">Ashe</option>
                <option data-img-src="./assets/heroes/Baptiste.png" value="Baptiste">Baptiste</option>
                <option data-img-src="./assets/heroes/Bastion.png" value="Bastion">Bastion</option>
                <option data-img-src="./assets/heroes/Brigitte.png" value="Brigitte">Brigitte</option>
                <option data-img-src="./assets/heroes/DVa.png" value="DVa">DVa</option>
                <option data-img-src="./assets/heroes/Doomfist.png" value="Doomfist">Doomfist</option>
                <option data-img-src="./assets/heroes/Echo.png" value="Echo">Echo</option>
                <option data-img-src="./assets/heroes/Genji.png" value="Genji">Genji</option>
                <option data-img-src="./assets/heroes/Hanzo.png" value="Hanzo">Hanzo</option>
                <option data-img-src="./assets/heroes/Junkrat.png" value="Junkrat">Junkrat</option>
                <option data-img-src="./assets/heroes/Lucio.png" value="Lucio">Lucio</option>
                <option data-img-src="./assets/heroes/McCree.png" value="McCree">McCree</option>
                <option data-img-src="./assets/heroes/Mei.png" value="Mei">Mei</option>
                <option data-img-src="./assets/heroes/Mercy.png" value="Mercy">Mercy</option>
                <option data-img-src="./assets/heroes/Moira.png" value="Moira">Moira</option>
                <option data-img-src="./assets/heroes/Orisa.png" value="Orisa">Orisa</option>
                <option data-img-src="./assets/heroes/Pharah.png" value="Pharah">Pharah</option>
                <option data-img-src="./assets/heroes/Reaper.png" value="Reaper">Reaper</option>
                <option data-img-src="./assets/heroes/Reinhardt.png" value="Reinhardt">Reinhardt</option>
                <option data-img-src="./assets/heroes/Roadhog.png" value="Roadhog">Roadhog</option>
                <option data-img-src="./assets/heroes/Sigma.png" value="Sigma">Sigma</option>
                <option data-img-src="./assets/heroes/Soldier 76.png" value="Soldier 76">Soldier 76</option>
                <option data-img-src="./assets/heroes/Sombra.png" value="Sombra">Sombra</option>
                <option data-img-src="./assets/heroes/Symmetra.png" value="Symmetra">Symmetra</option>
                <option data-img-src="./assets/heroes/Torbjorn.png" value="Torbjorn">Torbjorn</option>
                <option data-img-src="./assets/heroes/Tracer.png" value="Tracer">Tracer</option>
                <option data-img-src="./assets/heroes/Widowmaker.png" value="Widowmaker">Widowmaker</option>
                <option data-img-src="./assets/heroes/Winston.png" value="Winston">Winston</option>
                <option data-img-src="./assets/heroes/Wrecking Ball.png" value="Wrecking Ball">Wrecking Ball</option>
                <option data-img-src="./assets/heroes/Zarya.png" value="Zarya">Zarya</option>
                <option data-img-src="./assets/heroes/Zenyatta.png" value="Zenyatta">Zenyatta</option>
            </select>
        </template>
        <template id="map-select-template">
            <select data-placeholder="Blizzard World" name="comp[i]-map[]" id="comp[i]-map[j]"
                    onchange="showMap([i], [j]); return false" class="form-control form-control-chosen" disabled>
                <option value="Adlersbrunn">Adlersbrunn</option>
                <option value="Ayutthaya">Ayutthaya</option>
                <option value="Black Forest">Black Forest</option>
                <option value="Blizzard World" selected>Blizzard World</option>
                <option value="Busan">Busan</option>
                <option value="Castillo">Castillo</option>
                <option value="Dorado">Dorado</option>
                <option value="Ecopoint Antartica">Ecopoint Antartica</option>
                <option value="Eichenwalde">Eichenwalde</option>
                <option value="Hanamura">Hanamura</option>
                <option value="Havana">Havana</option>
                <option value="Hollywood">Hollywood</option>
                <option value="Horizon Lunar Colony">Horizon Lunar Colony</option>
                <option value="Ilios">Ilios</option>
                <option value="Junkertown">Junkertown</option>
                <option value="Kings Row">Kings Row</option>
                <option value="Lijiang Tower">Lijiang Tower</option>
                <option value="Necropolis">Necropolis</option>
                <option value="Nepal">Nepal</option>
                <option value="Numbani">Numbani</option>
                <option value="Oasis">Oasis</option>
                <option value="Paris">Paris</option>
                <option value="Rialto">Rialto</option>
                <option value="Route 66">Route 66</option>
                <option value="Temple of Anubis">Temple of Anubis</option>
                <option value="Volskaya Industries">Volskaya Industries</option>
                <option value="Watchpoint Gibraltar">Watchpoint Gibraltar</option>
            </select>
        </template>
        <template id="premade-comp-select-template">
            <div class="row">
                <div class="col-6 mt-4 btn-group" role="group">
                    <button type="button" class="btn btn-primary btn-sm" onclick="importComp([i]); return false">Import
                        Comp
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="saveCompToFile([i]); return false">
                        Export Comp
                    </button>
                    &nbsp;
                    <select name="comp[i]-premade" id="comp[i]-premade" onchange="loadComp([i]); return false"
                            class="form-control form-control-chosen">
                    </select><br/>
                </div>
                <input type="file" accept="application/json" name="comp[i]-file" id="comp[i]-file"
                       style="visibility: hidden;" onchange="loadCompFromCustomFile([i]); return false">
            </div>
        </template>
        <template id="premade-comp-select-options-template">
            <option value="[comp-name]">[comp-name]</option>
        </template>
        <!-- jQuery first, then Popper.js, then Bootstrap JS -->
        <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"
                integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
                crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
                integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
                crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/image-select/2.0/ImageSelect.jquery.js"></script>
        <!-- Optional JavaScript -->
        <script>
            function showHero(i, j) {
                let heroAmount = $("select#comp" + i + "-hero" + j).length;
                let imageAnchor = $("#comp" + i + "-img" + j + "-anchor");
                imageAnchor.html("");
                switch (heroAmount) {
                    case 1:
                        imageAnchor.html($("#single-hero-img-template").html().replace(/\[i]/g, i).replace(/\[j]/g, j));
                        break;
                    case 2:
                        imageAnchor.html($("#double-hero-img-template").html().replace(/\[i]/g, i).replace(/\[j]/g, j));
                        break;
                    case 3:
                        imageAnchor.html($("#triple-hero-img-template").html().replace(/\[i]/g, i).replace(/\[j]/g, j));
                        break;
                    case 4:
                        imageAnchor.html($("#quadra-hero-img-template").html().replace(/\[i]/g, i).replace(/\[j]/g, j));
                        break;
                    default:
                }
                let k = 0;
                $("select#comp" + i + "-hero" + j + " option:selected").each(function () {
                    let hero = $(this).val();
                    switch (k) {
                        case 0:
                            imageAnchor.html(imageAnchor.html().replace(/\[hero1]/g, hero));
                            break;
                        case 1:
                            imageAnchor.html(imageAnchor.html().replace(/\[hero2]/g, hero));
                            break;
                        case 2:
                            imageAnchor.html(imageAnchor.html().replace(/\[hero3]/g, hero));
                            break;
                        case 3:
                            imageAnchor.html(imageAnchor.html().replace(/\[hero4]/g, hero));
                            break;
                        default:
                    }
                    k++;
                });
            }

            function showMap(i, j) {
                let map = $("select#comp" + i + "-map" + j).val();
                $("#comp" + i + "-map" + j + "-image").attr("src", "./assets/maps/" + map + ".jpg");
            }

            function addHero(i, j) {
                if (compHeroes[i][j - 1] === 1) {
                    $("#comp" + i + "-addHero" + j).after($("#hero-select-template").html().replace(/\[i]/g, i).replace(/\[j]/g, j));
                } else {
                    $("#comp" + i + "-removeHero" + j).after($("#hero-select-template").html().replace(/\[i]/g, i).replace(/\[j]/g, j));
                }
                compHeroes[i][j - 1]++;
                if (compHeroes[i][j - 1] === 1) {
                    $("#comp" + i + "-removeHero" + j).remove();
                }
                if (compHeroes[i][j - 1] === 2) {
                    $("#comp" + i + "-addHero" + j).after($("#remove-hero-template").html().replace(/\[i]/g, i).replace(/\[j]/g, j));
                }
                if (compHeroes[i][j - 1] === 4) {
                    $("#comp" + i + "-addHero" + j).remove();
                }
                $("[id^=comp" + i + "].form-control-chosen").chosen();
                showHero(i, j);
            }

            function removeHero(i, j) {
                let selectedHero = $("select#comp" + i + "-hero" + j);
                selectedHero.last().chosen("destroy");
                selectedHero.last().remove();
                compHeroes[i][j - 1]--;
                if (compHeroes[i][j - 1] === 1) {
                    $("#comp" + i + "-removeHero" + j).remove().trigger("chosen:updated");
                }
                if (compHeroes[i][j - 1] === 3) {
                    $("#comp" + i + "-removeHero" + j).before($("#add-hero-template").html().replace(/\[i]/g, i).replace(/\[j]/g, j));
                }
                showHero(i, j);
            }

            function addMap(i) {
                $("#comp" + i + "-map" + compMaps[i] + "-anchor").after($("#add-map-template").html().replace(/\[i]/g, i).replace(/\[j]/g, compMaps[i] + 1));
                compMaps[i]++;
                $("#comp" + i + "-map" + compMaps[i] + "-select").replaceWith($("#map-select-template").html().replace(/\[i]/g, i).replace(/\[j]/g, compMaps[i]));
                let compMapAttr = $("#comp" + i + "-map" + compMaps[i]);
                compMapAttr.attr("disabled", !compMapAttr.attr("disabled"));
                $("[id^=comp" + i + "].form-control-chosen").chosen();
            }

            function removeMap(i) {
                $("#comp" + i + "-map" + compMaps[i] + "-anchor").remove();
                compMaps[i]--;
            }

            function addComp() {
                compAmount++;
                $("#row-anchor").before($("#comp-template").html().replace(/\[i]/g, compAmount));
                let j = 0;
                let compDiv = $("#comp" + compAmount);
                compDiv.find("div").each(function () {
                    j++;
                    $(this).replaceWith($("#comp-column-template").html().replace(/\[i]/g, compAmount).replace(/\[j]/g, j));
                });
                j = 0;
                compDiv.find("div #hero-select").each(function () {
                    j++;
                    $(this).replaceWith($("#hero-select-template").html().replace(/\[i]/g, compAmount).replace(/\[j]/g, j));
                });
                $("#comp" + compAmount + "-map1-select").replaceWith($("#map-select-template").html().replace(/\[i]/g, compAmount).replace(/\[j]/g, 1));
                compHeroes[compAmount] = [1, 1, 1, 1, 1, 1];
                compMaps[compAmount] = 1;
                if (compAmount > 1) {
                    location.href = "#comp" + compAmount;
                }
                loadCompSelectionBox(compAmount);
                $("[id^=comp" + compAmount + "].form-control-chosen").chosen();
            }

            function loadComp(i) {
                let name = $("#comp" + i + "-premade").children("option:selected").val();
                if (name !== "Load premade comp") {
                    loadCompFromFile(i, name);
                }
            }

            function importComp(i) {
                $("#comp" + i + "-file").click();
            }

            function loadCompFromCustomFile(i) {
                let reader = new FileReader();
                reader.onload = function () {
                    let json = JSON.parse(reader.result);
                    processCompFile(i, json);
                };
                reader.readAsText($("#comp" + i + "-file")[0].files[0]);
            }

            function loadCompFromFile(i, name) {
                let file = "./comps/" + name + ".json";
                $.getJSON(file, function (json) {
                    processCompFile(i, json);
                });
            }

            function processCompFile(i, json) {
                $("#comp" + i + "-name").attr("value", json.name);
                $("#comp" + i + "-desc").html(json.description);
                let j = 1;
                $.each(json.comp, function (key, value) {
                    let heroAmount = 1;
                    if ($.isArray(value)) {
                        heroAmount = value.length;
                    }
                    let compSelect = $("select#comp" + i + "-hero" + j);
                    let difference = compSelect.length - heroAmount;
                    if (difference < 0) {
                        for (let k = 0; k < Math.abs(difference); k++) {
                            addHero(i, j);
                        }
                    } else if (difference > 0) {
                        for (let k = 0; k < difference; k++) {
                            removeHero(i, j);
                        }
                    }
                    if ($.isArray(value)) {
                        for (let k = 0; k < heroAmount; k++) {
                            $("select#comp" + i + "-hero" + j).eq(k).val(value[k]);
                        }
                    } else {
                        let compSelect = $("select#comp" + i + "-hero" + j);
                        if (compSelect.length > 1) {
                            for (let k = 0; k <= compSelect.length; k++) {
                                removeHero(i, j);
                            }
                        }
                        compSelect.eq(0).val(value);
                    }
                    showHero(i, j);
                    j++;
                });
                compMaps[i] = 1;
                let compSelect = $("select#comp" + i + "-hero" + j);
                if (compSelect.length > 1) {
                    for (let k = 2; k <= $("[id^=comp" + i + "-map] select").length; k++) {
                        $("comp" + i + "-map" + k + "-anchor").remove();
                    }
                }
                let playerEnabled = $("input#comp" + i + "-player-enabled");
                if (json.playernames !== undefined) {
                    if (!playerEnabled.is(":checked")) {
                        playerEnabled.prop('checked', true);
                        togglePlayer(i);
                    }
                    $.each(json.playernames, function (key, value) {
                        $("input#comp" + i + "-player-names").eq(key).val(value);
                    });
                } else {
                    if (playerEnabled.is(":checked")) {
                        playerEnabled.prop('checked', false);
                        togglePlayer(i);
                    }
                }
                let l = 1;
                $.each(json.maps, function (key, value) {
                    if (l > 1) {
                        addMap(i);
                    } else {
                        let checkMapComp = $("#comp" + i + "-map-enabled");
                        if (!checkMapComp.prop("checked")) {
                            checkMapComp.prop("checked", true);
                            toggleMap(i);
                        }
                    }
                    $("#comp" + i + "-map" + l).val(value);
                    showMap(i, l);
                    l++;
                });
                let formControlChosen = $("[id^=comp" + i + "].form-control-chosen");
                formControlChosen.chosen();
                formControlChosen.trigger("chosen:updated");
            }

            function saveCompToFile(i) {
                let finalResult = {};
                finalResult["name"] = $("#comp" + i + "-name").val();
                let comp = [];
                let playerNames = [];
                for (let j = 1; j <= 6; j++) {
                    let compSelect = $("select#comp" + i + "-hero" + j);
                    if (compSelect.length > 1) {
                        let subHeroes = [];
                        for (let k = 0; k < compSelect.length; k++) {
                            subHeroes.push(compSelect.eq(k).val());
                        }
                        comp.push(subHeroes);
                    } else {
                        comp.push($("select#comp" + i + "-hero" + j).eq(0).val());
                    }
                    playerNames.push($("input#comp" + i + "-player-names").eq(j - 1).val())
                }
                let maps = [];
                for (let l = 0; l < $("[id^=comp" + 1 + "-map] select").length; l++) {
                    maps.push($("[id^=comp" + 1 + "-map] select").eq(l).val());
                }
                finalResult["playernames"] = playerNames;
                finalResult["comp"] = comp;
                finalResult["description"] = $("#comp" + i + "-desc").val();
                if ($("#comp" + i + "-map-enabled").val()) {
                    finalResult["maps"] = maps;
                }
                let dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(finalResult, null, 4));
                let linkElement = document.createElement('a');
                linkElement.setAttribute('href', dataUri);
                linkElement.setAttribute('download', finalResult["name"] + ".json");
                linkElement.click();
            }

            function loadCompSelectionBox(i) {
                $("#comp" + i).before($("#premade-comp-select-template").html().replace(/\[i]/g, i));
                $("#comp" + i + "-premade").html($("#premade-comp-select-options-template").html().replace(/\[comp-name]/g, "Load premade comp"));
                $.getJSON("./comps/getcomps.php", function (json) {
                    $.each(json, function (key, value) {
                        $("#comp" + i + "-premade").find("option").last().after($("#premade-comp-select-options-template").html().replace(/\[comp-name]/g, value));
                    });
                    $("#comp" + i + "-premade").trigger("chosen:updated");
                });
            }

            function toggleDesc(i) {
                let compDesc = $("#comp" + i + "-desc");
                compDesc.attr("disabled", !compDesc.attr("disabled"));
            }

            function togglePlayer(i) {
                let compPlayerNames = $("div #comp" + i + "-hero-player");
                compPlayerNames.toggle();
            }

            function toggleTitle() {
                $("#image-title").attr("disabled", !$("#image-title").attr("disabled"));
            }

            function toggleMap(i) {
                let compMapMatch = $("[id^=comp" + i + "-map] select");
                let compAddMap = $("#comp" + i + "-add-map");
                compMapMatch.attr("disabled", !compMapMatch.attr("disabled")).trigger("chosen:updated");
                compAddMap.attr("hidden", !compAddMap.attr("hidden"));
            }

            var compAmount = 0;
            var compHeroes = [];
            var compMaps = [];
            $.ajaxSetup({'cache': false});
            addComp();
        </script>
    </form>
    </body>
    </html>
    <?php
}
?>
