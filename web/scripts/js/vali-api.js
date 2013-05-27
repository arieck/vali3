/*jslint browser: true, undef: true, sub: true, continue: true, sloppy: true, windows: true, white: true, nomen: true, maxerr: 50, indent: 2 */
/*globals vali, alert, FileReader */

'use strict';

var vali = window.vali || {};

/**
* @param {string} url
* @constructor
*/
vali.Api = function (url)
{

  this.events_= [];
  this.items = [];
  this.xhr = [];
  
  this.itemsCount = 0;
  this.processed = 0;
  this.active = 0;
  this.lastIndex = -1;
  this.filter = '';
  this.dragDrop = false;
  this.cancelled = false;
  
  this.results = {
    'passed': 0,
    'failed': 0,
    'error': 0
  };
                   
  this.cn = {
    STATUS_NONE: 0,
    STATUS_ACTIVE: 1,
    STATUS_DONE: 2,
    ENC_NONE: '',
    ENC_GZIP: 'gzip',
    ENC_ZIP: 'zip'
  };
  
  this.apiUrl = url;
  this.boundary = '-----ivshell';
  this.maxXhr = 6;
  this.maxFileSize = 3000000;
    
  this.init_();

}; 


vali.Api.prototype.init_ = function ()
{

  var opts;  
  
  this.addEvent_(window, 'unload', this, this.onunloadPage);
  this.setResultFilter(true);
  
  if (!window.File || !window.FileList || !window.FileReader || !window.FormData)
  {
    this.get_('notcompatible').style.display = 'block';
    this.get_('multifiles').disabled = true;
    return;
  }
  
  this.setDragDrop();
  
  this.addEvent_('multifiles', 'change', this, this.fileHandler);
  this.addEvent_('btnClear', 'click', this, this.clear);
  this.addEvent_('btnCancel', 'click', this, this.cancel);
  this.addEvent_('btnValidate', 'click', this, this.validate);
  this.addEvent_('selFilter', 'change', this, this.filterItems);
  
  this.addEvent_('filesContainer', 'click', this, this.resultInfo);
  
  opts = {
    preventDefault: true,
    stopPropagation: true
  };
  
  if (this.dragDrop)
  {
    this.addEvent_('filesContainer', 'drop', this, this.fileHandler, opts);
    this.addEvent_('filesContainer', 'dragover', null, null, opts);
    this.get_('dropContainer').style.display = 'block';
  }
  
  this.boundary += Math.floor(Math.random() * Math.pow(10, 8));
                  
}; // init


vali.Api.prototype.setDragDrop = function ()
{

  var eventName, el;
  
  eventName = 'ondrop';
  el = document.createElement('div');
  this.dragDrop = el.hasOwnProperty(eventName);
  
  if (!this.dragDrop)
  {

    if (el.setAttribute && el.removeAttribute)
    {
      
      el.setAttribute(eventName, '');
      this.dragDrop = typeof el[eventName] === 'function';
      
      if (typeof el[eventName] !== undefined)
      {
        el[eventName] = undefined;
      }
      
      el.removeAttribute(eventName);
      
    }
    
  }

  el = null;
               
};  // setDragDrop


vali.Api.prototype.fileHandler = function (ev)
{

  var files, len, i, added, maxFiles, noDupes, s;
  
  maxFiles = this.get_('optMaxFiles').checked;
  noDupes = this.get_('optNoDupes').checked;
          
  this.itemsCount = this.items.length;
  
  files = ev.target.files || ev.dataTransfer.files;
    
  len = files.length;
  added = 0;
  
  for (i = 0; i < len; i += 1)
  {
    
    if (maxFiles && this.itemsCount - this.processed === 100)
    {
      s = added === 1 ? '1 file' :  added + ' files';
      alert(s + ' out of ' + len + ' added (max 100 allowed)');
      break;
    }
    
    /*
    if (!this.checkFileExtension(files[i].name))
    {
      continue;
    }
    */
    
    if (noDupes && this.isDuplicate(files[i].name))
    {
      continue;
    }

    this.items[this.itemsCount] = {
      file: files[i],
      enc: '',
      status: this.cn.STATUS_NONE,
      response: this.getResponseRec(null)
    };
       
    this.createItem(this.itemsCount);
    this.checkFile(this.itemsCount);
    this.itemsCount += 1;
    added += 1;
    
  }
       
  this.updateResults();
  this.setState();
  
  if (this.itemsCount - this.processed > 0)
  {
    this.get_('btnValidate').focus();
  }
                                
}; // fileHandler


vali.Api.prototype.fileHandlerEx = function (e)
{

  var files, len, i, index;
                  
  this.itemsCount = this.items.length;
  
  files = e.target.files || e.dataTransfer.files;
    
  len = files.length;
  
  index = -1;
  
  for (i = 0; i < len; i += 1)
  {
  
    if (this.checkFileExtension(files[i].name))
    {
      index = i;
      break;
    }
                        
  }
  
  if (index > -1)
  {
  
    for (i = 0; i < 500; i += 1)
    {  

      this.items[this.itemsCount] = {
        file: files[index],
        enc: '',
        status: this.cn.STATUS_NONE,
        response: this.getResponseRec(null)
      };
       
      this.createItem(this.itemsCount);
      this.checkFile(this.itemsCount);
      this.itemsCount += 1;
        
    }
  
  }
  
  this.updateResults();
  this.setState();
  
  if (this.itemsCount - this.processed > 0)
  {
    this.get_('btnValidate').focus();
  }
                                
}; // fileHandlerEx


vali.Api.prototype.isDuplicate = function (fileName)
{

  var s1, s2, i;
  
  s1 = fileName.toLowerCase();
  
  for (i = 0; i < this.itemsCount; i += 1)
  {
  
    s2 = this.items[i].file.name.toLowerCase();
    
    if (s1 === s2)
    {
      return true;
    }
  
  }
  
  return false;
  
}; // isDuplicate


vali.Api.prototype.createItem = function (index)
{

  var file, parent, div, divId, container;
  
  file = this.items[index].file;   
  
  parent = document.createElement('div');
  parent.id = this.getItemId(index);
  parent.className = 'fileItem';
  parent.style.display = this.filter ? 'none' : 'block';
  
  // filename
  div = document.createElement('div');
  div.className = 'filename';
  div.innerHTML = file.name;
  parent.appendChild(div);
    
  // filesize
  div = document.createElement('div');
  div.className = 'filesize';
  div.innerHTML = this.formatFileSize(file.size);
  parent.appendChild(div);
  
  // filestatus
  div = document.createElement('div');
  div.className = 'filestatus';
  
  // id div
  divId = document.createElement('div');
  divId.id = this.getFileId(index);
  divId.className = 'vali-none';
        
  div.appendChild(divId);
  parent.appendChild(div);
  
  // clearing div
  div = document.createElement('div');
  div.className = 'clear';
  parent.appendChild(div);
  
  container = this.get_('filesList');
  container.appendChild(parent);
  
  // output div
  div = document.createElement('div');
  div.className = 'outputContainer';
  div.style.display = 'none';
  div.id = this.getOutputId(index);
  container.appendChild(div);
    
}; // createItem


vali.Api.prototype.getFileId = function (index)
{

  return 'file-' + index;
  
}; // getFileId


vali.Api.prototype.getItemId = function (index)
{

  return 'item-' + index;
  
}; // getItemId


vali.Api.prototype.getOutputId = function (index)
{

  return 'output-' + index;
  
}; // getOutputId
   

vali.Api.prototype.checkFile = function (index)
{

  var file, result, reader, self, blob;
      
  file = this.items[index].file;
  result = this.getResponseRec(null);
    
  if (file.size > this.maxFileSize)
  {
    result.status = 'ERR_SIZE';
    result.msg = 'File too large - max ' + (this.maxFileSize / 1000000) + 'mb';
    this.updateStatus(index, this.cn.STATUS_DONE, result);
    return;  
  }
          
  if (file.slice)
  {
    blob = file.slice(0, 4);
  }    
  else if (file.webkitSlice)
  {
    blob = file.webkitSlice(0, 4);
  }
  else if (file.mozSlice)
  {
    blob = file.mozSlice(0, 4);
  }
  
  if (blob)
  {
  
    reader = new FileReader();      
    self = this;
      
    reader.onloadend = function (ev) {
      
      if (ev.target.readyState === FileReader.DONE)
      {
        self.checkFileCallback(index, ev.target.result); 
      }
      
    };
      
    reader.readAsBinaryString(blob);
  
  }
                  
}; // checkFile


vali.Api.prototype.checkFileExtension = function (fileName)
{

  return (/\.igc$/i).test(fileName);
  
}; // checkFileExtension


vali.Api.prototype.checkFileCallback = function (index, data)
{

  var ok, result;
  
  ok = false;
  
  if (/^A[A-Z]{1}[A-Z,0-9]{2}/.test(data))
  {
    ok = true;    
  }
  else if (/^\x50\x4B\x03\x04/.test(data))
  {
    ok = true; 
  }
  else if (/^\x1f\x8b\x08[\x00-\xff]{1}/.test(data))
  {
    ok = true; 
  }    
  
  if (!ok)
  {

    result = this.getResponseRec('ERROR');
    result.status = 'ERR_FORMAT';
    result.msg = 'Not an IGC file';
    
    this.updateStatus(index, this.cn.STATUS_DONE, result);    
    this.setState();
    
  }
     
}; // checkFileCallback


vali.Api.prototype.formatFileSize = function (bytes)
{

  var sizes, x, s;
  
  if (bytes < Math.pow(1024, 3))
  {
    
    x = (bytes / 1024).toFixed(0);
    
    if (parseInt(x, 10) === 0)
    {
      x = 1;
    }
        
    s = x + ' KB';
     
  }
  else
  {
    x = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)), 10);
    sizes = ['MB', 'GB', 'TB'];
    s = '<b>';
    s += (bytes / Math.pow(1024, x)).toFixed(1);
    s += ' ' + sizes[x - 2] + '</b>';
  }
  
  return s;
     
}; // formatFileSize


vali.Api.prototype.clearItems = function ()
{

  var parent;
  
  parent = this.get_('filesList');
  
  while (parent.firstChild)
  {
    parent.removeChild(parent.firstChild);
  }
              
}; // clearItems


vali.Api.prototype.setState = function ()
{

  var className, dropDisplay;
  
  if (!this.itemsCount)
  {
    this.get_('multifiles').disabled = false;
    this.get_('btnClear').disabled = true;
    this.get_('btnCancel').disabled = true;
    this.get_('btnValidate').disabled = true;
    dropDisplay = 'block';
    className = 'icon-wait-static';
  }
  else
  {
    this.get_('multifiles').disabled = this.active > 0;
    this.get_('btnClear').disabled = this.active > 0;
    this.get_('btnCancel').disabled = this.active === 0;   
    this.get_('btnValidate').disabled = this.active > 0 || this.itemsCount === this.processed;
    dropDisplay = 'none';
    className = this.active === 0 ? 'icon-wait-static' : 'icon-wait';
  }
  
  this.get_('progress').className = className;
  
  if (this.dragDrop)
  {
    this.get_('dropContainer').style.display = dropDisplay;
  }
  
}; // setState


vali.Api.prototype.validate = function ()
{

  var todo, len, i;
  
  this.cancelled = false;
  
  todo = this.itemsCount - this.processed;
  
  if (!todo)
  {
    return;
  }
        
  todo -= this.xhr.length;
  
  while (this.xhr.length < this.maxXhr && todo > 0)
  {
    this.xhr.push(this.xhrCreate());
    todo -= 1;  
  }  
 
  for (i = 0; i < this.itemsCount; i += 1)
  {
    
    if (this.items[i].status === this.cn.STATUS_NONE)
    {
      this.updateStatus(i, this.cn.STATUS_NONE, '<i>Waiting</i>'); 
    }
      
  }
  
  this.lastIndex = -1;
  len = this.xhr.length;
  
  for (i = 0; i < len; i += 1)
  {
    
    this.active += 1;
    
    if (i === 0)
    {
      this.setState();
    }
      
    this.findWork(this.xhr[i]);
    
  }

}; // validate


vali.Api.prototype.findWork = function (xhr)
{

  var i, found;
  
  for (i = this.lastIndex + 1; i < this.itemsCount; i += 1)
  {
  
    if (this.items[i].status === this.cn.STATUS_NONE)
    {
      found = true;
      this.lastIndex = i;
      this.doWork(xhr, i);
      break;
    }
    
  }
  
  if (!found)
  {
    this.active -= 1;
    this.setState();  
  }
    
}; // findWork


vali.Api.prototype.doWork = function (xhr, index)
{

  var formData;

  if (this.cancelled)
  {
    return;
  }
      
  this.updateStatus(index, this.cn.STATUS_ACTIVE, 'Validating');  
  
  formData = new FormData();
  
  formData.append('igcfile', this.items[index].file);
  formData.append('out', 'vali');
  formData.append('ref', index + 1);
  
  xhr.send(index, formData); 
    
}; // doWork


vali.Api.prototype.updateStatus = function (index, status, response)
{

  var el, parent, result, resultEx, className, text;
  
  el = this.get_(this.getFileId(index));
  
  if (el)
  {

    result = '';
    resultEx = '';
    className = '';
    text = '';
            
    this.items[index].status = status;
        
    if (typeof response === 'object')
    {

      this.processed += 1;
      this.items[index].response = response;
      
      result = response.result;
                  
      if (response.result === 'PASSED')
      {
        className = 'vali-passed';
        this.results.passed += 1;        
      }
      else
      {
        
        resultEx = '&nbsp;&nbsp;&nbsp;[' + response.status + ']';
        
        if (response.result === 'FAILED')
        {
          className = 'vali-failed';
          this.results.failed += 1;
        }
        else
        {
          className = 'vali-error';
          this.results.error += 1;
        }
        
      }
      
      text = '';
      
      if (response.server)
      {
        text += '<div class="toggle"></div>';
      }
      
      text += result + resultEx + '<br />';
      text += '<i>' + response.msg + ' </i>';
                 
    }
    else
    {
    
      if (status === this.cn.STATUS_ACTIVE)
      {
        className = 'vali-active';
      }
      else
      {
        className = 'vali-none';
      }
      
      text = response;
             
    }
             
    parent = this.get_(this.getItemId(index));
        
    if (result)
    {
      
      if (response.server)
      {
        parent.setAttribute('data-index', index);
        parent.setAttribute('class', 'fileItemData');
      }

      if (parent.style.display === 'none' && result === this.filter)
      {
        parent.style.display = 'block';
      }
      
    }
    
    el.className = className;
    el.innerHTML = text;
               
    
    if (result)
    {
      this.updateResults();
    }
                        
  }
  
}; // updateStatus


vali.Api.prototype.updateResults = function ()
{
            
  this.get_('result-files').innerHTML = this.itemsCount;
  this.get_('result-checked').innerHTML = this.processed;
  this.get_('result-passed').innerHTML = this.results.passed;
  this.get_('result-failed').innerHTML = this.results.failed;
  this.get_('result-error').innerHTML = this.results.error;
  
}; //updateResults


vali.Api.prototype.cancel = function ()
{

  var len, i;
  
  this.cancelled = true;
  
  len = this.xhr.length;
  
  for (i = 0; i < len; i += 1)
  {
    this.xhr[i].abort();
  }
  
  for (i = 0; i < this.itemsCount; i += 1)
  {
    
    if (this.items[i].status !== this.cn.STATUS_DONE)
    {
      this.updateStatus(i, this.cn.STATUS_NONE, '');
    }
    
  }  
  this.active = 0;
  this.setState();

}; // cancel


vali.Api.prototype.xhrCreate = function ()
{

  var context, xhr, url, boundary;
  
  context = this;
  url = this.apiUrl;
  boundary = this.boundary;
  
  xhr = new XMLHttpRequest();
     
  return (function ()
  {
  
    var id, self, aborted;
        
    xhr.onreadystatechange = function ()
    {  
      
      var status, response;
      
      if (xhr.readyState === 4)
      {  

        try
        {
          status = xhr.status;
          response = xhr.responseText;
        }
        catch (e)
        {
          status = 0;
          response = '';
        }
        
        context.xhrCallback(status, response, id, self, aborted);
        
      }
        
    }; // onreadystatechange
    
    
    function _open()
    {
      
      xhr.open('POST', url, true);
      //xhr.setRequestHeader('Content-Type', 'multipart/form-data, boundary=' + boundary);
          
    } // _open
    
    
    function _abort(check)
    {
      
      if (!check || xhr.readyState > 1)
      {
        
        aborted = true;
        xhr.abort();
        
        if (check)
        {
          _open();
        }
        
      }
          
    } // _abort 
    
    
    return {
    
      // public properties
            
      abort: function(check)
      {
        
        _abort(check);
                              
      }, // abort (public)
      
      
      send: function(fileId, data)
      {
        
        id = fileId;
        self = this;
                 
        if (xhr.readyState === 0)
        {
          _open();
        }
        else
        {
          _abort(true);
        }
        
        aborted = false;
        xhr.send(data);
        
      } // send (public)
    
    };  
    
  }());
     
}; // xhrCreate


vali.Api.prototype.xhrCallback = function (status, response, id, xhr, aborted)
{

  var result, json;
  
  if (aborted || this.cancelled)
  {
    return;
  }
  
  result = this.getResponseRec('ERROR');
         
  if (!status)
  {
    result.status = 'ERR_NETWORK';
    result.msg = 'No response from server';
  }
  else if (status !== 200)
  {
    
    if (status === 400)
    {
      result.status = 'ERR_INPUT';
      result.msg = 'Bad request';
    }
    else 
    {
      result.status = 'ERR_SERVICE';
      result.msg = 'HTTP response code ' + status;
    }
      
  }
  else
  {

    try
    {
      
      json = JSON.parse(response);
      result.result = json.result;
      result.status = json.status;
      result.msg = json.msg;
      result.igc = json.igc;
      result.ref = json.ref;
      result.server = json.server;
      result.output = json.output || '';
      
    }
    catch (e)
    {
      result.status = 'ERR_SERVICE';
      result.msg = 'Bad request';
    }  
  
  }
         
  this.updateStatus(id, this.cn.STATUS_DONE, result);
  this.findWork(xhr);

}; // xhrCallback


vali.Api.prototype.filterItems = function (e)
{

  var sel, i, el;
  
  sel = e.target;
  this.setResultFilter(false);
  this.filter = sel.options[sel.selectedIndex].value;
  this.setResultFilter(true);
        
  for (i = 0; i < this.itemsCount; i += 1)
  {
  
    el = this.get_(this.getItemId(i));
        
    if (!this.filter || this.filter === this.items[i].response.result)
    {
      el.style.display = 'block';
    }
    else
    {
      el.style.display = 'none';
    }
    
    this.get_(this.getOutputId(i)).style.display = 'none';
               
  }
 
}; // filterItems


vali.Api.prototype.setResultFilter = function (on)
{

  var el;
  
  el = this.get_('result-vali-' + this.filter.toLowerCase());
  
  if (el)
  {
    el.style.backgroundColor = on ? '#e5e6ff' : 'transparent';
  }
  
  el = this.get_('result-' + this.filter.toLowerCase());
  
  if (el)
  {
    el.style.fontWeight = on ? 'bold' : 'normal';
  }
  
}; // setResultFilter


vali.Api.prototype.clear = function ()
{
            
  this.clearItems();
           
  this.items = [];
  this.itemsCount = 0;
  this.active = 0;
  this.processed = 0;

  this.results.passed = 0;
  this.results.failed = 0;
  this.results.error = 0;
  
  this.cancelled = false;     
  
  this.setState();
  this.updateResults();
  
  if (this.filter)
  {
    this.get_('selFilter').selectedIndex = 0;
    this.setResultFilter(false);
    this.filter = '';
  }
  
  this.get_('multifiles').value = '';
   
}; // clear


vali.Api.prototype.resultInfo = function (e)
{

  var ids, ar, index, el, el2, x, y;
  
  x = window.pageXOffset;
  y = window.pageYOffset;
  
  // check for tab clicks first
  ids = e.target.getAttribute('data-tab');
  
  if (ids)
  {
    
    if (e.target.className === 'active')
    {
      return;
    }
    ar = ids.split(',');
    el = this.get_(ar[0]);
    el2 = this.get_(ar[1]);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
    el2.style.display = el.style.display === 'none' ? 'block' : 'none';
    
    ar = e.target.parentNode.getElementsByClassName('active');
    ar[0].className = '';
    e.target.className = 'active';
    
    window.scroll(x, y);
    
    return;
  }
  
  el = e.target;
  
  do
  {
    
    index = el.getAttribute('data-index');
    
    if (index)
    {
      break;
    }
    else
    {
      el = el.parentNode;
    }
    
  } while (el && el.id !== 'filesList');
     
  if (!index)
  {
    return;
  }
  
  index = parseInt(index, 10);
           
  el2 = this.get_(this.getOutputId(index));
  
  if (!el2)
  {
    return;
  }
        
  if (el2.style.display === 'none')
  {
      
    if (!el2.innerHTML)
    {
      this.getOutputDiv(el2, index);
    }
    
    el.className = 'fileItemSelected';
    el2.style.display = 'block';
                       
  }
  else if (el2.style.display === 'block')
  {
    el.className = 'fileItemData';
    el2.style.display = 'none';
  }
  
  window.scroll(x, y);
    
}; // resultInfo


vali.Api.prototype.getOutputDiv = function (parent, index)
{

  var response, output, el, outer, id12, id1, id2, s;
  
  response = this.items[index].response;
  output = response.output ? response.output.join('<br />') : '';
    
  id1 = output ? 'tab1-' + index : '';
  id2 = 'tab2-' + index;
  id12 = id1 ? id1 + ',' + id2 : '';
  
  // <div> output
  el = document.createElement('div');
  el.className = 'output';
  outer = parent.appendChild(el);
  
  // <div> tabs
  el = document.createElement('div');
  el.className = 'tabs';
  parent = outer.appendChild(el);   
          
  // <ul>
  el = document.createElement('ul');
  parent = parent.appendChild(el);   
  
  // <li> tab1      
  el = document.createElement('li');
  el.className = output ? 'active' : 'disabled';
  
  if (id12)
  {
    el.setAttribute('data-tab', id12);
  }
  
  el.innerHTML = 'Vali Output'; 
  parent.appendChild(el);
  
  // <li> tab2
  el = document.createElement('li');
  el.className = output ? '' : 'active';
  
  if (id12)
  {
    el.setAttribute('data-tab', id12);
  }
  
  el.innerHTML = 'API Response';
  parent.appendChild(el);
  
  // <div> content
  el = document.createElement('div');
  el.className = 'content';
  parent = outer.appendChild(el);  
      
  if (id1)
  {
    el = document.createElement('div');
    el.id = id1;
    el.className = 'vali-output';
    el.style.display = 'block';
    el.innerHTML = output;
    parent.appendChild(el);
  }
         
  el = document.createElement('div');
  el.id = id2;
  el.className = 'vali-response';
  el.style.display = id1 ? 'none' : 'block';
  
  s = '<table><tbody>';
  s += this.writeTableRow('result', response.result);
  s += this.writeTableRow('status', response.status);
  s += this.writeTableRow('msg', response.msg);
  s += this.writeTableRow('igc', response.igc);
  s += this.writeTableRow('ref', response.ref);
  s += this.writeTableRow('server', response.server);
  s += '</tbody></table>';
      
  el.innerHTML = s;
  
  parent.appendChild(el);
                                   
}; // getOutputDiv


vali.Api.prototype.writeTableRow = function (name, value)
{

  return '<tr><td class="label">' + name + '</td><td>' + value + '</td></tr>';

}; // writeTableRow


vali.Api.prototype.getResponseRec = function (result)
{

  return {
    result: result || '',
    status: '',
    msg: '',
    igc: '',
    ref: '',
    server: '',
    output: ''
  };
  
}; // getResponseRec


/**
 * @param {string} id The reference id of the element.
 * @return {Node}
 * @private
 */
vali.Api.prototype.get_ = function (id)
{
  
  return document.getElementById(id); 
   
};


/**
 * @param {string|Node|Window} node
 * @param {string} event
 * @param {Object} context
 * @param {Function} func
 * @param {Object=} opts
 */
vali.Api.prototype.addEvent_ = function (node, event, context, func, opts)
{

  var capture, preventDefault, stopPropagation, sendEvent, args, fn;
  
  opts = opts || {};
  capture = opts.capture || false;
  preventDefault = opts.preventDefault || false;
  stopPropagation = opts.stopPropagation || false;
  sendEvent = opts.sendEvent !== undefined ? opts.sendEvent : true;
  args = opts.args !== undefined ? opts.args : [];
      
  if (typeof node === 'string')
  {
    node = document.getElementById(node);
  }
  
  if (!node)
  {
    return;
  }  
    
  fn = function (e)
  {

    var eventArgs = [];
    
    if (preventDefault)
    {
    
      if (e.preventDefault)
      {
        e.preventDefault();
      }
      else
      {
        e.returnValue = false;
      }
            
    }
    
    if (stopPropagation)
    {
      e.stopPropagation();
    }   
    
    if (sendEvent)
    {    
    
      if (e.srcElement && !e.target)
      {
        e.target = e.srcElement;
      }
      
      eventArgs[0] = e;
    
    }
                
    return func.apply(context, eventArgs.concat(args));
    
  };
      
  if (node.addEventListener)
  {
    node.addEventListener(event, fn, capture);
  }
  else
  {
    node.attachEvent('on' + event, fn);
  }
  
  this.events_.push({node: node, event: event, listener: fn});
  
};


/**
 * Called by window.onunload
 * @public
 */
vali.Api.prototype.onunloadPage = function ()
{

  var len, i, fn;
    
  if (window.removeEventListener)
  {
  
    fn = function (rec)
    {
      rec.node.removeEventListener(rec.event, rec.listener, false);  
    };  
    
  }
  else 
  {

    fn = function (rec)
    {
      rec.node.detachEvent('on' + rec.event, rec.listener); 
    };
          
  }
  
  len = this.events_.length;
  
  for (i = 0; i < len; i += 1)
  {
    fn(this.events_[i]);  
  }
      
};


window['vali']['Api'] = vali.Api;

