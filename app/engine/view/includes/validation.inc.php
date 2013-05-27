<?php
  
  $basic = fn::getPageHref('validation-basic', 'Basic Service');
  $service = fn::getPageHref('webservice', 'Web Service');
  
  $this->addJsScript('vali-api.js');
  
  $url = Fx::getPath(Fx::PATH_WEB, 'api/vali/json');
  $js = "var api = new vali.Api('{$url}');";
  $this->addJs($js);
  
?>

<div id="notcompatible" class="noncompat" style="display: none;">
Your browser does not yet support this functionality. Please use the
<?php echo $basic; ?> instead
</div>

<noscript>
<div class="noncompat">
This page requires JavaScript to be enabled. Please use the
<?php echo $basic; ?> instead
</div>
</noscript>

<h1>Online Validation</h1>
<p>
Use this service to validate one or many igc files. If you need to do this on a regular basis,
it is better to use the
<?php echo $service; ?>.
</p>

<div id="jsonly" style="display: block;">

<input type="file" id="multifiles" name="multifiles[]" multiple style="width:100%;font-size: 12px;" />

    
<div id="options">
  <input type=checkbox id="optMaxFiles" checked="checked" /><label for="optMaxFiles">Max 100 files</label>
  <input type=checkbox id="optNoDupes" checked="checked" /><label for="optNoDupes">Filter duplicate file names</label>
</div>
  
<div id="multiContainer">

  <div id="filesHeader">
     
    <div id="result-vali-passed" class="header-item">
    Passed:<span id="result-passed">0</span>
    </div>

    <div id="result-vali-failed" class="header-item">
    Failed:<span id="result-failed">0</span>
    </div>

    <div id="result-vali-error" class="header-item">
    Errors:<span id="result-error">0</span>
    </div>


    <select id="selFilter">
    <option value="">All</option>
    <option value="PASSED">Passed</option>
    <option value="FAILED">Failed</option>
    <option value="ERROR">Errors</option>
    </select>

    <div id="result-vali-" class="header-item">
    Checked:<span id="result-checked">0</span>
    <b>Files:<span id="result-files">0</span></b> 
    </div>

    <input type="button" id="btnClear" value="Clear All" disabled="disabled" />
    <input type="button" id="btnCancel" value="Cancel" disabled="disabled" />
    <input type="button" id="btnValidate" value="Validate" disabled="disabled" />
    <div id="progress" class="icon-wait-static"></div> 

    <div class="clear"></div>
    
  </div>

  <div id="filesContainer">

    <div id="dropContainer">
      <div id="dropInfo">drag and drop files here</div>
    </div>  
    
    <div id="filesList">
    </div>
    
  </div>

</div>

<h2>How to use</h2> 

<p>
Select the files you wish to validate either by using the file input button or by dragging and dropping them into the
marked area, then click the <b>Validate</b> button. If cannot see a marked area, then this feature is not
supported by your browser.
</p>

<h2>File input</h2>
<p>
You can use any IGC file or compressed IGC file (zip, gzip).
You can continue to add files and validate them up to a maximum of 100. If you need more than this, untick the <b>Max 100 files</b> checkbox, although your browser may become unresponsive with large values.
You can use the drag and drop feature even when the marked area is no longer visible. If you are adding files from
different directories you may need to untick the <b>Filter duplicate file names</b> checkbox.
Use the <b>Clear All</b> button to empty the list of files and start again.
</p>

<h2>Validation results</h2>
<p>
Please be patient if you are checking a large number of files. Progress is reported and shows the 
number of files that have been checked. You can <b>Cancel</b> the validation at any time (and restart it again).
You can filter the records by PASSED, FAILED and ERROR. Service data is available when this icon
<span class="toggle" style="float:none;display:inline-block;"></span> is present -
click anywhere on the record to view it. 
</p>

</div>
              


