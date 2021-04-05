import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IfcNewEditProjectComponent } from './ifc-new-edit-project.component';

describe('IfcNewEditProjectComponent', () => {
  let component: IfcNewEditProjectComponent;
  let fixture: ComponentFixture<IfcNewEditProjectComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IfcNewEditProjectComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(IfcNewEditProjectComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
