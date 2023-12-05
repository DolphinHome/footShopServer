<?php
namespace addons\AliyunOss\SDK;

class FileMimeType {    
    const MIMETYPES = [
        "application/epub+zip"=>".epub",
        "application/fractals"=>".fif",
        "application/futuresplash"=>".spl",
        "application/hta"=>".hta",
        "application/mac-binhex40"=>".hqx",
        "application/ms-vsi"=>".vsi",
        "application/msaccess"=>".accdb",
        "application/msaccess.addin"=>".accda",
        "application/msaccess.cab"=>".accdc",
        "application/msaccess.exec"=>".accde",
        "application/msaccess.ftemplate"=>".accft",
        "application/msaccess.runtime"=>".accdr",
        "application/msaccess.template"=>".accdt",
        "application/msaccess.webapplication"=>".accdw",
        "application/msonenote"=>".one",
        "application/msword"=>".doc",
        "application/opensearchdescription+xml"=>".osdx",
        "application/pdf"=>".pdf",
        "application/pkcs10"=>".p10",
        "application/pkcs7-mime"=>".p7c",
        "application/pkcs7-signature"=>".p7s",
        "application/pkix-cert"=>".cer",
        "application/pkix-crl"=>".crl",
        "application/postscript"=>".ps",
        "application/vnd.ms-excel"=>".xls",
        "application/vnd.ms-excel.12"=>".xlsx",
        "application/vnd.ms-excel.addin.macroEnabled.12"=>".xlam",
        "application/vnd.ms-excel.sheet.binary.macroEnabled.12"=>".xlsb",
        "application/vnd.ms-excel.sheet.macroEnabled.12"=>".xlsm",
        "application/vnd.ms-excel.template.macroEnabled.12"=>".xltm",
        "application/vnd.ms-officetheme"=>".thmx",
        "application/vnd.ms-pki.certstore"=>".sst",
        "application/vnd.ms-pki.pko"=>".pko",
        "application/vnd.ms-pki.seccat"=>".cat",
        "application/vnd.ms-powerpoint"=>".ppt",
        "application/vnd.ms-powerpoint.12"=>".pptx",
        "application/vnd.ms-powerpoint.addin.macroEnabled.12"=>".ppam",
        "application/vnd.ms-powerpoint.presentation.macroEnabled.12"=>".pptm",
        "application/vnd.ms-powerpoint.slide.macroEnabled.12"=>".sldm",
        "application/vnd.ms-powerpoint.slideshow.macroEnabled.12"=>".ppsm",
        "application/vnd.ms-powerpoint.template.macroEnabled.12"=>".potm",
        "application/vnd.ms-publisher"=>".pub",
        "application/vnd.ms-visio.viewer"=>".vsd",
        "application/vnd.ms-word.document.12"=>".docx",
        "application/vnd.ms-word.document.macroEnabled.12"=>".docm",
        "application/vnd.ms-word.template.12"=>".dotx",
        "application/vnd.ms-word.template.macroEnabled.12"=>".dotm",
        "application/vnd.ms-wpl"=>".wpl",
        "application/vnd.ms-xpsdocument"=>".xps",
        "application/vnd.oasis.opendocument.presentation"=>".odp",
        "application/vnd.oasis.opendocument.spreadsheet"=>".ods",
        "application/vnd.oasis.opendocument.text"=>".odt",
        "application/vnd.openxmlformats-officedocument.presentationml.presentation"=>".pptx",
        "application/vnd.openxmlformats-officedocument.presentationml.slide"=>".sldx",
        "application/vnd.openxmlformats-officedocument.presentationml.slideshow"=>".ppsx",
        "application/vnd.openxmlformats-officedocument.presentationml.template"=>".potx",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"=>".xlsx",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.template"=>".xltx",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document"=>".docx",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.template"=>".dotx",
        "application/windows-appcontent+xml"=>".appcontent-ms",
        "application/x-compress"=>".z",
        "application/x-compressed"=>".solitairetheme8",
        "application/x-dtcp1"=>".dtcp-ip",
        "application/x-gzip"=>".gz",
        "application/x-itunes-itls"=>".itls",
        "application/x-itunes-itms"=>".itms",
        "application/x-itunes-itpc"=>".itpc",
        "application/x-jtx+xps"=>".jtx",
        "application/x-latex"=>".latex",
        "application/x-mix-transfer"=>".nix",
        "application/x-mplayer2"=>".asx",
        "application/x-ms-application"=>".application",
        "application/x-ms-vsto"=>".vsto",
        "application/x-ms-wmd"=>".wmd",
        "application/x-ms-wmz"=>".wmz",
        "application/x-ms-xbap"=>".xbap",
        "application/x-mswebsite"=>".website",
        "application/x-pkcs12"=>".p12",
        "application/x-pkcs7-certificates"=>".p7b",
        "application/x-pkcs7-certreqresp"=>".p7r",
        "application/x-podcast"=>".pcast",
        "application/x-shockwave-flash"=>".swf",
        "application/x-stuffit"=>".sit",
        "application/x-tar"=>".tar",
        "application/x-troff-man"=>".man",
        "application/x-wmplayer"=>".asx",
        "application/x-x509-ca-cert"=>".cer",
        "application/x-zip-compressed"=>".zip",
        "application/xaml+xml"=>".xaml",
        "application/xhtml+xml"=>".xht",
        "application/xml"=>".xml",
        "application/zip"=>".zip",
        "audio/3gpp"=>".3gp",
        "audio/3gpp2"=>".3g2",
        "audio/aac"=>".aac",
        "audio/aiff"=>".aiff",
        "audio/amr"=>".amr",
        "audio/basic"=>".au",
        "audio/ec3"=>".ec3",
        "audio/l16"=>".lpcm",
        "audio/mid"=>".mid",
        "audio/midi"=>".mid",
        "audio/mp3"=>".mp3",
        "audio/mp4"=>".m4a",
        "audio/MP4A-LATM"=>".m4a",
        "audio/mpeg"=>".mp3",
        "audio/mpegurl"=>".m3u",
        "audio/mpg"=>".mp3",
        "audio/vnd.dlna.adts"=>".adts",
        "audio/vnd.dolby.dd-raw"=>".ac3",
        "audio/wav"=>".wav",
        "audio/x-aiff"=>".aiff",
        "audio/x-flac"=>".flac",
        "audio/x-m4a"=>".m4a",
        "audio/x-m4r"=>".m4r",
        "audio/x-matroska"=>".mka",
        "audio/x-mid"=>".mid",
        "audio/x-midi"=>".mid",
        "audio/x-mp3"=>".mp3",
        "audio/x-mpeg"=>".mp3",
        "audio/x-mpegurl"=>".m3u",
        "audio/x-mpg"=>".mp3",
        "audio/x-ms-wax"=>".wax",
        "audio/x-ms-wma"=>".wma",
        "audio/x-wav"=>".wav",
        "image/bmp"=>".dib",
        "image/gif"=>".gif",
        "image/jpeg"=>".jpg",
        "image/jps"=>".jps",
        "image/mpo"=>".mpo",
        "image/pjpeg"=>".jpg",
        "image/png"=>".png",
        "image/pns"=>".pns",
        "image/svg+xml"=>".svg",
        "image/tiff"=>".tif",
        "image/vnd.ms-dds"=>".dds",
        "image/vnd.ms-photo"=>".wdp",
        "image/x-emf"=>".emf",
        "image/x-icon"=>".ico",
        "image/x-png"=>".png",
        "image/x-wmf"=>".wmf",
        "midi/mid"=>".mid",
        "model/vnd.dwfx+xps"=>".dwfx",
        "model/vnd.easmx+xps"=>".easmx",
        "model/vnd.edrwx+xps"=>".edrwx",
        "model/vnd.eprtx+xps"=>".eprtx",
        "pkcs10"=>".p10",
        "pkcs7-mime"=>".p7m",
        "pkcs7-signature"=>".p7s",
        "pkix-cert"=>".cer",
        "pkix-crl"=>".crl",
        "text/calendar"=>".ics",
        "text/css"=>".css",
        "text/directory"=>".vcf",
        "text/directory;profile=vCard"=>".vcf",
        "text/html"=>".html",
        "text/plain"=>".txt",
        "text/scriptlet"=>".wsc",
        "text/vcard"=>".vcf",
        "text/x-component"=>".htc",
        "text/x-ms-contact"=>".contact",
        "text/x-ms-iqy"=>".iqy",
        "text/x-ms-odc"=>".odc",
        "text/x-ms-rqy"=>".rqy",
        "text/x-vcard"=>".vcf",
        "text/xml"=>".xml",
        "video/3gpp"=>".3gpp",
        "video/3gpp2"=>".3gp2",
        "video/avi"=>".avi",
        "video/mp4"=>".mp4",
        "video/mpeg"=>".mpeg",
        "video/mpg"=>".mpeg",
        "video/msvideo"=>".avi",
        "video/quicktime"=>".mov",
        "video/vnd.dece.mp4"=>".uvu",
        "video/vnd.dlna.mpeg-tts"=>".tts",
        "video/wtv"=>".wtv",
        "video/x-m4v"=>".m4v",
        "video/x-matroska"=>".mkv",
        "video/x-mpeg"=>".mpeg",
        "video/x-mpeg2a"=>".mpeg",
        "video/x-ms-asf"=>".asx",
        "video/x-ms-asf-plugin"=>".asx",
        "video/x-ms-dvr"=>".dvr-ms",
        "video/x-ms-wm"=>".wm",
        "video/x-ms-wmv"=>".wmv",
        "video/x-ms-wmx"=>".wmx",
        "video/x-ms-wvx"=>".wvx",
        "video/x-msvideo"=>".avi",
        "vnd.ms-pki.certstore"=>".sst",
        "vnd.ms-pki.pko"=>".pko",
        "vnd.ms-pki.seccat"=>".cat",
        "x-pkcs12"=>".p12",
        "x-pkcs7-certificates"=>".p7b",
        "x-pkcs7-certreqresp"=>".p7r",
        "application/vnd.android.package-archive"=>".apk",
        "application/vnd.android.obb"=>".obb",
        "x-x509-ca-cert"=>".cer",
        "application/json"=>".json",
    ];
    
    public static function getExt($mimeType){
        return self::MIMETYPES[$mimeType] ?? null;
    }
}