import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LeftNavBarComponent } from './left-nav-bar.component';
import { FontAwesomeModule } from '@fortawesome/angular-fontawesome';
import { NgbModule } from '@ng-bootstrap/ng-bootstrap';

describe('LeftNavBarComponent', () => {
  let component: LeftNavBarComponent;
  let fixture: ComponentFixture<LeftNavBarComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [LeftNavBarComponent],
      imports: [NgbModule, FontAwesomeModule],
    }).compileComponents();

    fixture = TestBed.createComponent(LeftNavBarComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
