      
  function fileUpload(form)
  {
    
    "use strict";
    var iframe = document.createElement("iframe");
    iframe.setAttribute("id","frameUpload");
    iframe.setAttribute("name","frameUpload");
    iframe.setAttribute("width","0");
    iframe.setAttribute("height","0");
    iframe.setAttribute("border","0");
    iframe.setAttribute("style","width: 0; height: 0; border: none;");
      
    form.parentNode.appendChild(iframe);
    window.frames.frameUpload.name = "frameUpload";
     
    var iframeId = document.getElementById("frameUpload");
    
    var eventHandler = function() {
     
      var console, div;
      
      if (!iframeId)
      {
        return;
      }
      
      if (iframeId.removeEventListener)
      {
        iframeId.removeEventListener("load", eventHandler, false);
      }
      else
      {
        iframeId.detachEvent("onload", eventHandler);
      }
             
      if (iframeId.contentDocument)
      {
        console = iframeId.contentDocument.getElementById('console');
      }
      else if (iframeId.contentWindow)
      {
        console = iframeId.contentWindow.document.getElementById('console');
      } else if (iframeId.document)
      {
        console = iframeId.document.getElementById('console');
      }
      
      if (!console)
      {
        console = {innerHTML: 'ERROR<br />Service error'};
      }
      
      div = document.getElementById("console");
      div.innerHTML = console.innerHTML;
      div.style.display = "block";
      div.scrollTop = div.scrollHeight;
      document.getElementById("back").style.display = "block"; 
      document.getElementById("console-form").style.display = "none";
      
      form.go.disabled = false;
      form.cancel.disabled = true;
      document.getElementById("wait").style.display = "none";
                         
      setTimeout(function() {
        iframeId.parentNode.removeChild(iframeId);
      }, 50);
      
    };
   
    if (iframeId.addEventListener)
    {
      iframeId.addEventListener("load", eventHandler, true);
    }
    else
    {
      iframeId.attachEvent("onload", eventHandler);
    }
     
    // Set properties
    form.setAttribute("target", "frameUpload");
    form.go.disabled = true;
    form.cancel.disabled = false;
    document.getElementById("wait").style.display = 'block';
                  
  }
  
  function init()
  {
  
    "use strict";
    var form, el;
    
    form = document.forms.igcform;
    
    document.forms.igcform.cancel.style.display = 'block';
    document.forms.igcform.cancel.disabled = true;
               
    form.onsubmit = function() {                        
      fileUpload(form);
      return true;
    };
  
    form.cancel.onclick = function() {

        var iframeId = document.getElementById("frameUpload");
        
        document.getElementById("wait").style.display = "none";
        document.forms.igcform.cancel.disabled = true;
                          
        setTimeout(function() {
          
          if (iframeId)
          {
            iframeId.parentNode.removeChild(iframeId);
          }
          
          document.forms.igcform.go.disabled = false;
                    
        }, 100);
        
        return false;    
    
    };
    
    
    el = document.getElementById("back");
    
    el.onclick = function() {
      
      document.getElementById("console").style.display = "none";
      document.getElementById("back").style.display = "none"; 
      document.getElementById("console-form").style.display = "block";
      return false;
      
    };
      
  }
  
  window.onload = init;
