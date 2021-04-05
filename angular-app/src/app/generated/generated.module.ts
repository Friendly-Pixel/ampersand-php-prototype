import { NgModule } from "@angular/core";
import { CommonModule } from "@angular/common";
import { FormsModule } from "@angular/forms";
import { IfcNewEditProjectComponent } from "./ifc-new-edit-project/ifc-new-edit-project.component";
import { TemplatesModule } from "../templates/templates.module";

@NgModule({
  declarations: [IfcNewEditProjectComponent],
  imports: [CommonModule, TemplatesModule, FormsModule],
  exports: [IfcNewEditProjectComponent],
})
export class GeneratedModule {}
