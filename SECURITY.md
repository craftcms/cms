# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability, please review these guidelines before submitting a report. We take security seriously and do our best to resolve security issues as quickly as possible.

## Guidelines

While working to identify potential security vulnerabilities, we ask that you:

- Share any issues you discover with us via [Github](https://github.com/craftcms/cms/security/advisories) or [our website](https://craftcms.com/contact) as soon as possible.
- Give us a reasonable amount of time to address any reported issues before publicizing them.
- Only report issues that are [in scope](#scope).
- Provide a quality report with precise explanations and concrete attack scenarios.
- Make sure you’re aware of the versions of Craft and Commerce that are actively [receiving security fixes](https://craftcms.com/knowledge-base/supported-versions).

## Scope

We are only interested in vulnerabilities that affect Craft or [first party Craft plugins](https://github.com/craftcms), tested against **your own local installation of the software**. You can install a local copy of Craft by following these [installation instructions](https://craftcms.com/docs/installing). Do **not** test against any Craft installation that you don’t own, including [craftcms.com](https://craftcms.com) or [demo.craftcms.com](https://demo.craftcms.com).

### Qualifying Vulnerabilities

- [Cross-Site Scripting (XSS)](https://en.wikipedia.org/wiki/Cross-site_scripting)
- [Cross-Site Request Forgery (CSRF)](https://en.wikipedia.org/wiki/Cross-site_request_forgery)
- [Arbitrary Code Execution](https://en.wikipedia.org/wiki/Arbitrary_code_execution)
- [Privilege Escalation](https://en.wikipedia.org/wiki/Privilege_escalation)
- [SQL Injection](https://en.wikipedia.org/wiki/SQL_injection)
- [Session Hijacking](https://en.wikipedia.org/wiki/Session_hijacking)

### Non-Qualifying Vulnerabilities

- Reports from automated tools or scanners
- Theoretical attacks without proof of exploitability
- Attacks that can be guarded against by following our [security recommendations](https://craftcms.com/guides/securing-craft).
- Server configuration issues outside of Craft’s control
- [Denial of Service](https://en.wikipedia.org/wiki/Denial-of-service_attack) attacks
- [Brute force attacks](https://en.wikipedia.org/wiki/Brute-force_attack) (e.g. on password or token hashes)
- Username or email address enumeration
- Social engineering of Pixel & Tonic staff or users of Craft installations
- Physical attacks against Craft installations
- Attacks involving physical access to a user’s device, or involving a device or network that’s already seriously compromised (e.g. [man-in-the-middle attacks](https://en.wikipedia.org/wiki/Man-in-the-middle_attack))
- Attacks that are the result of a third party Craft plugin should be reported to the plugin’s author
- Attacks that are the result of a third party library should be reported to the library maintainers
- Bugs that rely on an unlikely user interaction (i.e. the user effectively attacking themselves)
- Disclosure of tools or libraries used by Craft and/or their versions
- Issues that are the result of a user clearly ignoring common security best practices (like sharing their password publicly)
- Missing security headers which do not lead directly to a vulnerability via proof of concept
- Vulnerabilities affecting users of outdated/unsupported browsers or platforms
- Vulnerabilities affecting outdated versions of Craft
- Any behavior that is clearly documented
- Issues discovered while scanning a site you don’t own without permission
- Missing CSRF tokens on forms (unless you have a proof of concept, many forms either don’t need CSRF or are mitigated in other ways) and “logout” CSRF attacks
- [Open redirects](https://www.owasp.org/index.php/open_redirect)

## Bounties

To show our appreciation for the work it can take to find and report a vulnerability, we’re happy to offer researchers a monetary reward.

Reward amounts vary depending upon the severity. Our minimum reward for a qualifying vulnerability report is $50 USD and we expect to pay $500+ USD for major vulnerabilities.

A report will qualify for a bounty if:

- Our [Guidelines](#guidelines) have been followed in full.
- The vulnerability was previously unknown to us, or your report provides more information or shows the vulnerability to be more extensive than we originally thought.
- The vulnerability is non-trivial.
