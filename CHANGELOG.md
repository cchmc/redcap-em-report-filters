# 1.0.9 (2025-09-26)

### Non-Breaking "Breaking" Changes

- Download button: There is now project-level settings that enables/disables the download button on the report page and public reports. This is set to disabled by default for existing installations to avoid unexpected changes in functionality. To get this functionality back, enable the setting in the project settings page.

### Bug Fixes

- Fixed CSRF issue with downloading the report.
- Other fixes to ensure proper dropdown functionality in the filters.
- Fixed URL parameter filtering to work with column indexes or the named fields (e.g. first_name_0, last_name_0). This also allows for backward compatibility with URLs that were created before this change.
- Implemented security to avoid URL manipulation to access reports that the user does not have access to. If a user attempts to download a report they do not have access to, they will receive an error message.

### Features

- Added a new project setting to allow/disallow downloading the report from the report page. This is separate from the existing setting that allows/disallows downloading the report from public survey links.

- Added a new project setting to allow/disallow downloading the report from public survey links.

- If the report is paginated, on download attempt, a warning will be displayed indicating that 'All' records must be selected to allow download. This is necessary based on how the download works.

# 1.0.8 (2024-11-14)

### Bug Fixes

- Fix where emails and links were not being properly formatted in the filters. [Community Post](https://redcap.vumc.org/community/post.php?id=240135&comment=243905)

# 1.0.7 (2024-10-23)

### Bug Fixes

- Fix issue with using Report Tweaks external module causing mismatch of columns
- Fix issue with searching where values have numbers in parenthesis at the end
- Fix issue with exporting. The module not will not provide a download button if the user does not have export rights in the project. Additionally, the export follows the same logic as the normal REDCap export in terms of user rights. If a user does not have export rights to a field, that field will not be included in the export.

# 1.0.6 (2024-09-04)

### Bug Fixes

- Escaping text to avoid REDCap Security Scan errors
- Refactor Citation HTML to be AJAX loaded
- Fix issue with report_id not being submitted to the export script

# 1.0.5 (2024-09-04)

### Features

- Honor REDCap user permissions. The export will now honor REDCap user permissions just like the normal export. If a user does not export rights to fields included in the report, those fields will be excluded from the export.
- Include all citations: The export window will now display all citations that should be cited based on project settings/usage.

### Bug Fixes

- Comma separated values in the report will now be properly escaped. This will prevent the export from breaking when a comma is included in a field value.
- Escape value in searching. The searching uses a regular expression to find the value in the report. This regular expression was not properly escaping special characters. This has been fixed.
- Erroneous removal of ending parenthesis in the report. The report was removing the ending parenthesis from values when it shouldn't have been. This has been fixed.

# 1.0.4 (2024-08-03)

### Bug Fixes

- Fixes an issue when also using the Report Tweaks external module.
- Fixes as issue when Custom Record Label is used, and the report is downloaded. The %nbsp; character was being converted to Â. This has been fixed to be a regular space.

# 1.0.2 (2024-07-30)

### Features

- Minor visual update
- Add download capability to the report

# 1.0.1 (2024-07-29)

- Initial public release
