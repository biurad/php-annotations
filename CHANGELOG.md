CHANGELOG
=========

1.0
===

* [BC BREAK] Renamed the getAnnotation listener method to getAnnotations
* [BC BREAK] Added string list return type for the listener's getAnnotation method
* [BC BREAK] Use array instead of parsing annotation/attribute into class objects
* [BC BREAK] Changed the annotation's loader class listener method from variadic to add one at a time
* [BC BREAK] Changed the annotation's loader class build method from public to protected
* [BC BREAK] The annotation's loader load method no longer accepts null as a value
* Added alias support listener support for easier loading of listener's annotation/attribute
* Improved performance of loading annotation/attribute
