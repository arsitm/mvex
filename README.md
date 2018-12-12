mvex is a small app to make some load via requests a web application by links from sitemap.xml.

## Installation

Download the latest release from GitHub [Releases](https://github.com/arsitm/mvex/releases).

Or require globally using Composer with `composer create-project arsitm/mvex`. This will automatically add the `mvex` binary to your path.

## Usage

Usage:  
`mvex map <url> [<c>]`

Arguments:  
```
  url                      The URL of sitemap.xml
  c                        Concurrency [default: 1]
```

Example:  

- Get requests with 100 concurrency to URLs from `http://127.0.0.1:8000/sitemap.xml`

`./mvex.phar map http://127.0.0.1:8000/sitemap.xml 100`

Also support https://github.com/vamsiikrishna/vex/blob/master/README.md